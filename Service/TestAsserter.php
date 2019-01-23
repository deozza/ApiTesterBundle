<?php
namespace Deozza\ApiTesterBundle\Service;

use Deozza\ApiTesterBundle\Service\TestFactorySetup;
use Symfony\Component\HttpFoundation\Response;

class TestAsserter extends TestFactorySetup
{
    protected function launchTestByKind($kind, $test)
    {
        if($kind == "unit")
        {
            $this->assertTest($test);
        }
        elseif($kind == "scenario")
        {
            foreach($test as $item)
            {
                $this->assertTest($item);
            }
        }
    }

    private function assertTest($test)
    {
        $in = null;
        $out = [];
        if(array_key_exists('in', $test))
        {
            $in = $this->loadJsonFile($test['in']);
        }

        if(array_key_exists('out', $test))
        {
            $out = json_decode($this->loadJsonFile($test['out']), true);
        }

        $headers["CONTENT-TYPE"] = "application/json";

        if(array_key_exists('token', $test))
        {
            $headers['X-AUTH-TOKEN'] = $test['token'];
        }

        $this->client->request(
            $test['method'],
            $test['url'],
            [],
            [],
            $headers,
            $in, $headers['CONTENT-TYPE']
        );

        $this->assertStatusCode($this->client->getResponse(),$test['status']);
        $this->assertResponseBody($this->client->getResponse(), $out);
    }

    private function loadJsonFile($in)
    {
        return file_get_contents($in.".json");
    }

    private function assertStatusCode(Response $response, $expectedStatus)
    {
        $responseStatus = $response->getStatusCode();
        $this->assertEquals($responseStatus, $expectedStatus);
    }

    private function assertResponseBody(Response $response, $expectedBody)
    {
        $responseBody = $response->getContent();

        if(empty($responseBody))
        {
            $responseBody = [];
        }
        else
        {
            $responseBody = json_decode($responseBody, true);
        }

        $comparator = new \TreeWalker(
            [
                "debug"=>true,
                "returntype"=> "array"
            ]
        );

        $result = $comparator->getdiff($responseBody, $expectedBody, true);

        $this->assertEquals($result['new']    ,[] , $this->errorMessage("These properies are in excess"                , $result['new']    ));

        $this->assertEquals($result['removed'],[] , $this->errorMessage("These properties are absent from the response",$result['removed'] ));

        $this->assertEquals($result['edited'] ,[] , $this->errorMessage("A property does not have the expected value"  ,$result['edited']  ));
    }

    private function errorMessage($message, $value)
    {
        $errorMessage = $message." : \n";

        foreach($value as $key=>$variable)
        {
            $errorMessage .= "At key ".$key." : ".json_encode($variable)."\n";
        }

        return $errorMessage;
    }
}