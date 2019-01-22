<?php
namespace Deozza\ApiTesterBundle\Service;

use Deozza\ApiTesterBundle\Service\TestFactorySetup;

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

        if(array_key_exists('in', $test))
        {
            $in = $this->loadJsonFile($test['in']);
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

        $this->assertEquals($test['status'], $this->client->getResponse()->getStatusCode());
    }

    private function loadJsonFile($in)
    {
        return file_get_contents($in.".json");
    }
}