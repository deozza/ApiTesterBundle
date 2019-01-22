<?php
namespace Deozza\ApiTesterBundle\Service;

use GuzzleHttp\Client;

class RequestSender
{
    public function __construct()
    {
        $this->guzzleclient = new Client([
            'base_uri' => "http://127.0.0.1:8000/api/",
            'timeout' => 30.0,
        ]);
    }
    public function sendRequest($method, $endpoint, $in = null, $header = null)
    {
        $body['http_errors'] = false;
        if(!empty($in)) $body['json'] = $this->loadJsonFile($in);
        if(!empty($header)) $body['headers'] =[
            "X-AUTH-TOKEN" => $header
        ];
        return $this->guzzleclient->request($method, $endpoint, $body);
    }
    private function loadJsonFile($in)
    {
        return json_decode(file_get_contents($in.".json"));
    }
}