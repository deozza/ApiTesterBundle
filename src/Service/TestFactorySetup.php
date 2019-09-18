<?php
namespace Deozza\PhilarmonyApiTesterBundle\Service;

use Deozza\PhilarmonyApiTesterBundle\Exception\MissingKeyException;
use Deozza\PhilarmonyApiTesterBundle\Exception\TestDatabaseNotFoundException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Dotenv\Dotenv;

class TestFactorySetup extends WebTestCase
{
    protected $client;
    protected $application;

    public function setTestDatabasePath(string $testDatabasePath): self
    {
        $this->testDatabasePath = $testDatabasePath;
        return $this;
    }

    private function getTestDatabasePath(): ?string
    {
        return $this->testDatabasePath;
    }

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
        $this->databasePath = $this->getTestDatabasePath();

        if(empty($this->databasePath))
        {
            throw new MissingKeyException("You must define the database used for the test");
        }

        if(!file_exists($this->databasePath))
        {
            throw new TestDatabaseNotFoundException($this->databasePath." does not exist");
        }

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->getClientOrCreateOne();

        $dotenv = new Dotenv();
        $folder  = static::$kernel->getProjectDir();
        $dotenv->load($folder . '/.env');
        $this->dbManager  = getenv('DB_MANAGER');
        $this->resetDb();

    }
    protected function runCommand($command)
    {
        $command = sprintf('%s --quiet', $command);
        return $this->getApplicationOrCreateOne()->run(new StringInput($command));
    }
    protected function tearDown()
    {
        $this->resetDb();
        parent::tearDown();
    }

    private function resetDb()
    {
        switch($this->dbManager)
        {
            case 'mysql':
                {
                    $this->runCommand('d:database:import '.$this->getTestDatabasePath());
                }
                break;
            case 'sqlite':
                {
                    $file_in  = $this->getTestDatabasePath();
                    $file_out = $folder.substr(getenv('DATABASE_URL'), 30);

                    if(!file_exists($file_in))
                    {
                        copy($file_out,$file_in);
                    }

                    copy($file_in,$file_out);
                }
                break;
            case 'mongodb':
                {
                    if(!is_dir($this->getTestDatabasePath()))
                    {
                        throw new TestDatabaseNotFoundException("$this->getTestDatabasePath() is not a valid test database or does not exist.", 1);
                    }
                    shell_exec('mongorestore --drop --quiet --db '.getenv('MONGODB_DB').' '.$this->getTestDatabasePath());
                }
                break;
            default: throw new TestDatabaseNotFoundException("$this->dbManager is not handled. Valid database managers are 'mysql', 'mongodb' and 'sqlite'.", 1);
                break;
        }
    }
}