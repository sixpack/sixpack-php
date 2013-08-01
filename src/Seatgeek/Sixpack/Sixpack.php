<?php

include 'Response.php';

class Sixpack
{
    // configuration
    protected $base_url = 'http://localhost:5000';
    protected $cookiePrefix = 'sixpack';
    protected $autoForce = true;

    protected $alternatives = array();
    protected $clientId = null;
    protected $force = null;
    protected $experimentName = null;
    protected $ipAddress = null;
    protected $control = null;
    protected $userAgent = null;
    protected $kpi = null;

    // STATIC HELPER METHODS
    public static function simple_participate($experimentName, array $alternatives, $clientId = null, $force = null)
    {
        $klass = get_called_class();
        $sp = new $klass;
        $sp->setExperimentName($experimentName);
        $sp->setAlternatives($alternatives);

        if ($clientId) {
            $sp->setClientId($clientId);
        }

        if ($force && in_array($force, $alternatives)) {
            $sp->force = $force;
        }

        return $sp->participate()->getAlternative();
    }

    public static function simple_convert($experimentName, $clientId = null, $kpi = null)
    {
        $klass = get_called_class();
        $sp = new $klass;
        $sp->setExperimentName($experimentName);
        $sp->setClientId($clientId);
        $sp->setKpi($kpi);

        return $sp->convert()->getStatus();
    }

    public function setExperimentName($experiment)
    {
        $this->experimentName = $experiment;
    }

    public function setKpi($kpi)
    {
        $this->kpi = $kpi;
    }

    public function setAlternatives(array $alternatives)
    {
        $this->control = $alternatives[0];
        $this->alternatives = $alternatives;
    }

    public function setClientId($clientId = null)
    {
        $cookieName = $this->cookiePrefix . '_client_id';
        if ($clientId === null) {
          if (isset($_COOKIE[$cookieName])) {
            $clientId = $_COOKIE[$cookieName];
          } else {
            $clientId = $this->generateClientId();
          }
        }
        $this->clientId = $clientId;
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

    public function isForced() {
        $forceKey = "sixpack-force-".$this->experimentName;
        if (in_array($forceKey, array_keys($_GET))) {
            return true;
        }
        return false;
    }

    public function forceAlternative()
    {
        $forceKey = "sixpack-force-".$this->experimentName;
        $forcedAlt = $_GET[$forceKey];

        if (!in_array($forcedAlt, $this->alternatives)) {
            throw new Exception("Invalid forced alternative");
        }

        $mockJson = '{"status": "ok", "alternative": { "name": "'.$forcedAlt.'" }, "experiment": { "version": 0, "name": "show-bieber" },
                     "client_id": "null"
        }';
        $mockMeta = array('http_code' => 200, 'called_url' => '');


        return array($mockJson, $mockMeta);
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

    protected function setServerQueryParams()
    {
        $ua = $this->getUserAgent();
        $ip = $this->getIpAddress();

        if ($ua !== null) {
            $this->userAgent = $ua;
        }

        if ($ip !== null) {
            $this->ipAddress = $ip;
        }
    }

    protected function validateRequest()
    {

        if ($this->clientId === null) {
            throw new Exception("Client ID must not be null");
        }

        if (!preg_match('#^[a-z0-9][a-z0-9\-_ ]*$#i', $this->experimentName)) {
            throw new Exception("Invalid Experiment Name: $this->experimentName");
        }

        if ($this->endpoint == 'participate' && count($this->alternatives) < 2) {
            throw new Exception("At least two alternatives are required");
        }

        foreach ($this->alternatives as $alt) {
            if (!preg_match('#^[a-z0-9][a-z0-9\-_ ]*$#i', $alt)) {
                throw new Exception("Invalid Alternative Name: {$alt}");
            }
        }
    }

    protected function buildQueryParams() {

        if ($this->clientId === null) {
            $this->setClientId();
        }

        $this->setServerQueryParams();
        $this->validateRequest();

        return array(
            'experiment' => $this->experimentName,
            'alternatives' => $this->alternatives,
            'client_id' => $this->clientId,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'kpi' => $this->kpi
        );
    }

    protected function sendRequest($endpoint = '')
    {
        if ($this->isForced()) {
            return $this->forceAlternative();
        }

        $this->endpoint = $endpoint;
        $params = $this->buildQueryParams();

        $url = $this->base_url . '/' . $endpoint;

        $params = preg_replace('/%5B(?:[0-9]+)%5D=/', '=', http_build_query($params));
        $url .= '?' . $params;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0.25);

        $return = curl_exec($ch);
        $meta = curl_getinfo($ch);

        // handle failures in call dispatcher
        return array($return, $meta);
    }

}
