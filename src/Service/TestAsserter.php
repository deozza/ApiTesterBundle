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
        if(!array_key_exists('in', $test))
        {
            $test['in'] = null;
        }

        if(!array_key_exists('token', $test))
        {
            $test['token'] = null;
        }
        $response = $this->requestSender->sendRequest($test['method'], $test['url'], $test['in'], $test['token']);
        $this->assertEquals($test['status'], $response->getStatusCode());
    }
}