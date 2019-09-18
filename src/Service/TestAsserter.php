<?php
namespace Deozza\PhilarmonyApiTesterBundle\Service;

use Deozza\PhilarmonyApiTesterBundle\Exception\EnvMismatchException;
use Deozza\PhilarmonyApiTesterBundle\Exception\ExtraKeyException;
use Deozza\PhilarmonyApiTesterBundle\Exception\MissingKeyException;
use Deozza\PhilarmonyApiTesterBundle\Exception\TypeMismatchException;
use Deozza\PhilarmonyApiTesterBundle\Exception\TypeUnknownException;
use Deozza\PhilarmonyApiTesterBundle\Exception\ValueMismatchException;
use Symfony\Component\HttpFoundation\Response;

class TestAsserter extends TestFactorySetup
{

    const REGEXP_ASSERT_TYPE = "/^@\w+@/";
    private $env = [];

    public function setEnv(array $env): self
    {
        $this->env = $env;
        return $this;
    }
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
                $this->launchTestByKind("unit", $item);
            }
        }
    }

    private function assertTest($test)
    {
        $in = null;
        $out = null;
        if(array_key_exists('in', $test))
        {
            $in = $this->loadJsonFile($test['in']);
            $inAsObject = json_decode($in, true);
            foreach($inAsObject as $key=>$value)
            {
                $inAsObject[$key] = $this->replaceValue($value);
            }

            $in = json_encode($inAsObject);
        }

        foreach($test as $key=>$value)
        {
            $test[$key]= $this->replaceValue($value);
        }

        $headers["CONTENT-TYPE"] = "application/json";

        if(array_key_exists('token', $test))
        {
            $headers['HTTP_AUTHORIZATION'] = "Bearer:".$test['token'];
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

        if(array_key_exists('out', $test))
        {
            $out = json_decode($this->loadJsonFile($test['out'], false), true);

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
                throw new ExtraKeyException($key." is not expected to be in response. Expected response is : \n".json_encode($expectedResponse)."\n \n Got : \n".json_encode($response));
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
                throw new MissingKeyException($key." is missing in response. Expected response is : \n\".json_encode($expectedResponse).\"\n \n Got : \n\".json_encode($response)");
            }
        }
    }

    private function assertValue($responseValue, $expectedValue, $key)
    {
        $explodedExpectedValue = explode(".", $expectedValue);
        if(preg_match(self::REGEXP_ASSERT_TYPE, $explodedExpectedValue[0], $matches, PREG_OFFSET_CAPTURE))
        {
            $type = $this->determineTypeFromRegexp($matches[0][0]);

            if(substr($type, 0, 1) == "\\")
            {
                if(is_a($responseValue, $type))
                {
                    throw new TypeMismatchException("\n\n$key is supposed to be a $type. Is ".gettype($responseValue). " instead.".print_r($this->fullContext));
                }
            }
            else
            {
                if($type != gettype($responseValue))
                {
                    throw new TypeMismatchException("\n\n$key is supposed to be a $type. Is ".gettype($responseValue). " instead.".print_r($this->fullContext));
                }
            }

            for($i=1; $i<count($explodedExpectedValue); $i++)
            {
                $function = explode("(", $explodedExpectedValue[$i]);
                $this->{$function[0]}($function[1], $responseValue, $key);
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
            "@date@" => "\DateTime"
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

    private function catchAs($name, $value, $key)
    {
        $this->env[substr($name, 0, strlen($name)-1)] = $value;
    }

    private function isUuid($name, $value, $key)
    {
        $uuidRegex = "/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/";
        $match = preg_match($uuidRegex, $value);
        $this->assertEquals(1, $match, $key." is not a valid uuid");
    }

    private function valueLesserThan($expectedValue, $value, $key)
    {
        $expectedValue = substr($expectedValue, 0, strlen($expectedValue)-1);
        $this->assertLessThan($expectedValue, $value);
    }

    private function valueLesserThanOrEqual($expectedValue, $value, $key)
    {
        $expectedValue = substr($expectedValue, 0, strlen($expectedValue)-1);
        $this->assertLessThanOrEqual($expectedValue, $value);
    }
    private function valueGreaterThan($expectedValue, $value, $key)
    {
        $expectedValue = substr($expectedValue, 0, strlen($expectedValue)-1);
        $this->assertGreaterThan($expectedValue, $value);
    }
    private function valueGreaterThanOrEqual($expectedValue, $value, $key)
    {
        $expectedValue = substr($expectedValue, 0, strlen($expectedValue)-1);
        $this->assertGreaterThanOrEqual($expectedValue, $value);
    }
    private function before($expectedValue, $value, $key)
    {
        $expectedValue = substr($expectedValue, 0, strlen($expectedValue)-1);
        $dateToCompare = new \DateTime("now");

        if(strlen($expectedValue)>1)
        {
            if(substr($expectedValue, 0, 1) === "-")
            {
                $dateToCompare->sub(new \DateInterval(substr($expectedValue, 1, strlen($expectedValue)-2)));

            }
            else
            {
                $dateToCompare->add(new \DateInterval($expectedValue));
            }
        }

        $dateToCompare->getTimestamp();

        $this->assertLessThan($dateToCompare, $value);
    }
    private function after($expectedValue, $value, $key)
    {
        $expectedValue = substr($expectedValue, 0, strlen($expectedValue)-1);
        $dateToCompare = new \DateTime("now");

        if(strlen($expectedValue)>1)
        {
            if(substr($expectedValue, 0, 1) === "-")
            {
                $dateToCompare->sub(new \DateInterval(substr($expectedValue, 1, strlen($expectedValue)-2)));

            }
            else
            {
                $dateToCompare->add(new \DateInterval($expectedValue));
            }
        }

        $dateToCompare->getTimestamp();

        $this->assertGreaterThan($dateToCompare, $value);
    }
    private function replaceValue($toReplace)
    {
        $replaceValue = [];

        if(!preg_match('/#(\w+)#/', $toReplace, $replaceValue))
        {
            return $toReplace;
        }
        if(!array_key_exists($replaceValue[1], $this->env))
        {
            throw new EnvMismatchException($replaceValue[1]." has not been catched in a previous test");
        }

        $toReplace = str_replace($replaceValue[0], $this->env[$replaceValue[1]], $toReplace);
        return $toReplace;
    }
}