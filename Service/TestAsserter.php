<?php
namespace Deozza\ApiTesterBundle\Service;

use Deozza\ApiTesterBundle\Exception\ExtraKeyException;
use Deozza\ApiTesterBundle\Exception\MissingKeyException;
use Deozza\ApiTesterBundle\Exception\TypeMismatchException;
use Deozza\ApiTesterBundle\Exception\TypeUnknownException;
use Deozza\ApiTesterBundle\Exception\ValueMismatchException;
use Symfony\Component\HttpFoundation\Response;

class TestAsserter extends TestFactorySetup
{

    const REGEXP_ASSERT_TYPE = "/^@\w+@/";

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
            $out = json_decode($this->loadJsonFile($test['out'], false), true);
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

        if($out)
        {

            $responseBody = $this->client->getResponse()->getContent();

            if(json_decode($responseBody) == null)
            {
                $responseBody = [];
            }
            else
            {
                $responseBody = json_decode($responseBody, true);
            }
            $this->fullContext = $responseBody;
            $this->exportVarFromJsonArray($responseBody, $out);
        }
    }

    private function loadJsonFile($filename, $defaultDir = "/Payloads/")
    {
        $calledClass = get_called_class();
        $jsonDir = $defaultDir ? "/Payloads/" : "/Responses/";

        $location = dirname((new \ReflectionClass($calledClass))->getFileName()).$jsonDir;
        return file_get_contents($location.$filename.".json");
    }

    private function assertStatusCode(Response $response, $expectedStatus)
    {
        $responseStatus = $response->getStatusCode();
        $this->assertEquals($responseStatus, $expectedStatus);
    }

    private function exportVarFromJsonArray($response, $expectedResponse)
    {

        foreach($response as $key=>$value)
        {
            $keyExists = array_key_exists($key, $expectedResponse);

            if(!$keyExists)
            {
                throw new ExtraKeyException($key." is not expected to be in response");
            }

            if(is_array($value) && is_array($expectedResponse[$key]))
            {
                if(is_array($expectedResponse[$key]))
                {
                    $this->exportVarFromJsonArray($value, $expectedResponse[$key]);
                }
            }
            else
            {
                $this->assertValue($value, $expectedResponse[$key], $key);
            }
        }

        foreach($expectedResponse as $key=>$value)
        {
            if(!array_key_exists($key, $response))
            {
                throw new MissingKeyException($key." is missing in response");
            }
        }
    }

    private function assertValue($responseValue, $expectedValue, $key)
    {
        if(preg_match(self::REGEXP_ASSERT_TYPE, $expectedValue, $matches, PREG_OFFSET_CAPTURE))
        {
            $type = $this->determineTypeFromRegexp($matches[0][0]);
            if($type != gettype($responseValue))
            {
                throw new TypeMismatchException("\n\n$key is supposed to be a $type. Is ".gettype($responseValue). " instead.".print_r($this->fullContext));
            }
        }
        else
        {
            if($responseValue != $expectedValue)
            {
                throw new ValueMismatchException("\n\n$key value expected to be $expectedValue. Got $responseValue instead.".print_r($this->fullContext));
            }
        }
    }

    private function determineTypeFromRegexp($regexp)
    {
        $typeArray = [
            "@string@" => "string",
            "@int@" => "integer",
            "@integer@" => "integer",
            "@bool@" => "boolean",
            "@boolean@" => "boolean",
            "@double@" => "double",
            "@float@" => "double",
        ];

        $specialTypeArray = [

        ];

        if(array_key_exists($regexp, $typeArray))
        {
            return $typeArray[$regexp];
        }
        elseif(array_key_exists($regexp, $specialTypeArray))
        {
            return;
        }
        else
        {
            throw new TypeUnknownException("The type $regexp is not suppported");
        }
    }
}