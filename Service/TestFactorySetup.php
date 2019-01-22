<?php
namespace Deozza\ApiTesterBundle\Service;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Dotenv\Dotenv;

class TestFactorySetup extends WebTestCase
{
    protected $client;
    protected $application;
    public function getClientOrCreateOne()
    {
        if (null === $this->client)
        {
            $this->client = static::createClient();
        }
        return $this->client;
    }
    public function getApplicationOrCreateOne()
    {
        if (null === $this->application)
        {
            $this->application = new Application($this->getClientOrCreateOne()->getKernel());
            $this->application->setAutoExit(false);
        }
        return $this->application;
    }
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;
        $this->getClientOrCreateOne();

        $dotenv = new Dotenv();
        $folder  = static::$kernel->getProjectDir();
        $dotenv->load($folder . '/.env');
        $dbPath  = getenv('DATABASE_URL');

        if(substr($dbPath,0,8) == 'mysql://')
        {
            $this->runCommand('d:database:import var/data/db_test/demo.sql');
        }
        else if(substr($dbPath,0,10) == 'sqlite:///')
        {
            $file_in  = $folder."/var/data/db_test/test.sqlite";

            $file_out = substr($dbPath, 10);

            if(!file_exists($file_in))
            {
                copy($file_out,$file_in);
            }

            copy($file_in,$file_out);
        }
        else
        {
            throw new \Exception("Bad test_database_url parameter", 1);
        }
    }
    protected function runCommand($command)
    {
        $command = sprintf('%s --quiet', $command);
        return $this->getApplicationOrCreateOne()->run(new StringInput($command));
    }
    protected function tearDown()
    {
        parent::tearDown();
    }

}