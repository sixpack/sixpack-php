<?php namespace SeatGeek\Sixpack\Response;

class Base
{
    protected $response = null;
    protected $meta = null;

    public function __construct($jsonResponse, $meta)
    {
        $this->response = json_decode($jsonResponse);
        $this->meta = $meta;
    }

    public function getSuccess()
    {
        return ($this->meta['http_code'] === 200);
    }

    public function getStatus()
    {
        return $this->meta['http_code'];
    }

    public function getCalledUrl()
    {
        return $this->meta['url'];
    }

    public function getClientId()
    {
        return $this->response->client_id;
    }
}
