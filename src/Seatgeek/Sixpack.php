<?php namespace Seatgeek;

use Seatgeek\Sixpack\Response;

/**
 * TODO
 * DocBlocks
 * Sort namespaces and fix autoloader
 */

class Sixpack
{
    // configuration
    private $host = 'http://localhost';
    private $port = 8000;
    private $cookiePrefix = 'sixpack';
    private $autoForce = true;

    private $clientId = null;
    private $control = null;
    private $queryParams = array(
        'client_id' => null,
        'alternatives' => null,
        'force' => null,
        'experiment' => null,
        'ip_address' => null,
        'user_agent' => null
    );

    // STATIC HELPER METHODS
    public static function simple_participate($experimentName, array $alternatives, $clientId = null, $force = null)
    {
        $sp = new Sixpack;
        $sp->setExperimentName($experimentName);
        $sp->setAlternatives($alternatives);

        if ($clientId) {
            $sp->setClientId($clientId);
        }

        if ($force && in_array($force, $alternatives)) {
            $sp->forceAlternative($force);
        }

        return $sp->participate()->getAlternative();
    }

    public static function simple_convert($experimentName, $clientId = null)
    {
        $sp = new Sixpack;
        $sp->setExperimentName($experimentName);
        $sp->setClientId($clientId);

        return $sp->convert()->getStatus();
    }

    public function setExperimentName($experiment)
    {
        $this->queryParams['experiment'] = $experiment;
    }

    public function setAlternatives(array $alternatives)
    {
        $this->control = $alternatives[0];
        $this->queryParams['alternatives'] = $alternatives;
    }

    // TODO Allow client_id override
    public function setClientId($clientId = null)
    {
        $cookieName = $this->cookiePrefix . ':client_id';
        $uuid = $this->generateClientId();

        if (isset($_COOKIE[$cookieName]) && $clientId === null) {
            $this->queryParams['client_id'] = $_COOKIE[$cookieName];
        } elseif ($clientId !== null) {
            $this->queryParams['client_id'] = $clientId;
            setcookie($cookieName, $clientId);
        } else {
            $this->queryParams['client_id'] = $uuid;
            setcookie($cookieName, $uuid);
        }
    }

    private function generateClientId()
    {
        // This is just a first pass for testing. not actually unique.
        // TODO, NOT THIS
        $md5 = strtoupper(md5(uniqid(rand(), true)));
        $clientId = substr($md5, 0, 8) . '-' . substr($md5, 8, 4) . '-' . substr($md5, 12, 4) . '-' . substr($md5, 16, 4) . '-' . substr($md5, 20);
        return $clientId;
    }

    public function forceAlternative($alternative)
    {
        $this->queryParams['force'] = $alternative;
    }

    public function status()
    {
        return $this->sendRequest('/_status');
    }

    public function convert()
    {
        list($rawResp, $meta) = $this->sendRequest('convert');
        $respObj = new ConversionResponse($rawResp, $meta);

        return $respObj;
    }

    public function participate()
    {
        list($rawResp, $meta) = $this->sendRequest('participate');

        // I really don't want to have to pass the control here, but I'm not sure what else to do.
        $respObj = new ParticipationResponse($rawResp, $meta, $this->control);

        return $respObj;
    }

    private function getUserAgent()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            return $_SERVER['HTTP_USER_AGENT'];
        }
        return null;
    }

    private function getIpAddress()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return null;
    }

    private function setServerQueryParams()
    {
        $ua = $this->getUserAgent();
        $ip = $this->getIpAddress();

        if ($ua !== null) {
            $this->queryParams['user_agent'] = $ua;
        }

        if ($ip !== null) {
            $this->queryParams['ip_address'] = $ip;
        }
    }

    private function validateRequest()
    {
        // VALID STRING REGEX
        // ^[a-z0-9][a-z0-9\-_ ]*$


        // ensure an experiment name is given, and validates with regex
        // ensure at leat two alternatives, and they both validate with regex
        // -- participate only
        // ensure that a client id is present
        // throw argumentexception
    }

    private function sendRequest($endpoint = '')
    {
        $this->setServerQueryParams();

        if ($this->queryParams['client_id'] === null) {
            $this->setClientId();
        }

        if ($this->autoForce && isset($_GET['sixpack-force'])) {
            $this->forceAlternative($_GET['sixpack-force']);
        }

        $this->validateRequest();

        // TODO
        // this is also going to go away.
        // composer, requests dependancy.
        $url = $this->host;
        if ($this->port !== null) {
            $url .= ':' . $this->port;
        }
        $url .= '/' . $endpoint;

        $params = preg_replace('/%5B(?:[0-9]+)%5D=/', '=', http_build_query($this->queryParams));
        $url .= '?' . $params;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $return = curl_exec($ch);
        $meta = curl_getinfo($ch);

        // handle failures in call dispatcher
        return array($return, $meta);
    }

    // PSR-1 Autoloader
    public static function autoload($className)
    {
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        require 'Sixpack/'.$fileName;
    }

    public static function register_autoloader()
    {
        spl_autoload_register(array('\Sixpack', 'autoload'));
    }
}