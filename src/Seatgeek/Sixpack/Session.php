<?php namespace Sixpack\Session;

include 'Response/Response.php';

use Sixpack\Response;

class Base {
    // configuration
    protected $baseUrl = 'http://localhost:5000';
    protected $cookiePrefix = 'sixpack';

    protected $clientId = null;

    public function __construct($options = array())
    {
        if (isset($options["baseUrl"])) {
            $this->baseUrl = $options["baseUrl"];
        }
        if (isset($options["cookiePrefix"])) {
            $this->cookiePrefix = $options["cookiePrefix"];
        }
        $this->setClientId(isset($options["clientId"]) ? $options["clientId"] : null);
    }

    protected function setClientId($clientId = null)
    {
        if ($clientId === null) {
            $clientId = $this->retrieveClientId();
        }
        if ($clientId === null) {
            $clientId = $this->generateClientId();
        }
        $this->clientId = $clientId;
        $this->storeClientId($clientId);
    }

    public function getClientid()
    {
        return $this->clientId;
    }

    protected function retrieveClientId()
    {
        $cookieName = $this->cookiePrefix . '_client_id';
        if (isset($_COOKIE[$cookieName])) {
            return $_COOKIE[$cookieName];
        }
    }

    protected function storeClientId($clientId)
    {
        $cookieName = $this->cookiePrefix . '_client_id';
        setcookie($cookieName, $clientId, time() + (60 * 60 * 24 * 30 * 100), "/");
    }

    protected function generateClientId()
    {
        // This is just a first pass for testing. not actually unique.
        // TODO, NOT THIS
        $md5 = strtoupper(md5(uniqid(rand(), true)));
        $clientId = substr($md5, 0, 8) . '-' . substr($md5, 8, 4) . '-' . substr($md5, 12, 4) . '-' . substr($md5, 16, 4) . '-' . substr($md5, 20);
        return $clientId;
    }

    public function isForced($experiment)
    {
        $forceKey = "sixpack-force-" . $experiment;
        if (in_array($forceKey, array_keys($_GET))) {
            return true;
        }
        return false;
    }

    protected function forceAlternative($experiment, $alternatives)
    {
        $forceKey = "sixpack-force-" . $experiment;
        $forcedAlt = $_GET[$forceKey];

        if (!in_array($forcedAlt, $alternatives)) {
            throw new \Exception("Invalid forced alternative");
        }

        $mockJson = json_encode(array(
          "status" => "ok",
          "alternative" => array("name" => $forcedAlt),
          "experiment" => array("version" => 0, "name" => $experiment),
          "client_id" => null,
        ));
        $mockMeta = array('http_code' => 200, 'called_url' => '');

        return array($mockJson, $mockMeta);
    }

    public function status()
    {
        return $this->sendRequest('/_status');
    }

    public function convert($experiment, $kpi = null)
    {
        list($rawResp, $meta) = $this->sendRequest('convert', array(
          "experiment" => $experiment,
          "kpi" => $kpi,
        ));
        return new Response\Conversion($rawResp, $meta);
    }

    public function participate($experiment, $alternatives)
    {
        if (count($alternatives) < 2) {
            throw new \Exception("At least two alternatives are required");
        }
        foreach ($alternatives as $alt) {
            if (!preg_match('#^[a-z0-9][a-z0-9\-_ ]*$#i', $alt)) {
                throw new \Exception("Invalid Alternative Name: {$alt}");
            }
        }

        if ($this->isForced($experiment)) {
            list($rawResp, $meta) = $this->forceAlternative($experiment, $alternatives);
        } else {
            list($rawResp, $meta) = $this->sendRequest('participate', array(
                "experiment" => $experiment,
                "alternatives" => $alternatives,
            ));
        }

        return new Response\Participation($rawResp, $meta, $alternatives[0]);
    }

    protected function getUserAgent()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            return $_SERVER['HTTP_USER_AGENT'];
        }
        return null;
    }

    protected function getIpAddress()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return null;
    }

    protected function sendRequest($endpoint, $params = array())
    {
        if (isset($params["experiment"]) && !preg_match('#^[a-z0-9][a-z0-9\-_ ]*$#i', $params["experiment"])) {
            throw new \Exception("Invalid Experiment Name: " . $params["experiment"]);
        }

        $params = array_merge(array(
            'client_id' => $this->clientId,
            'ip_address' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
        ), $params);

        $url = $this->baseUrl . '/' . $endpoint;

        $params = preg_replace('/%5B(?:[0-9]+)%5D=/', '=', http_build_query($params));
        $url .= '?' . $params;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
        // Make sub 1 sec timeouts work, according to: http://ravidhavlesha.wordpress.com/2012/01/08/curl-timeout-problem-and-solution/
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);

        $return = curl_exec($ch);
        $meta = curl_getinfo($ch);

        // handle failures in call dispatcher
        return array($return, $meta);
    }
}

class Temp extends Base {

    protected function retrieveClientId()
    {
        return;
    }

    protected function storeClientId($clientId)
    {
        return;
    }

}
