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
        $databasePath = $this->getTestDatabasePath();

        if(empty($databasePath))
        {
            throw new MissingKeyException("You must define the database used for the test");
        }

        if(!file_exists($databasePath))
        {
            throw new TestDatabaseNotFoundException($databasePath." does not exist");
        }

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->getClientOrCreateOne();

        $dotenv = new Dotenv(true);
        $folder  = static::$kernel->getProjectDir();
        $dotenv->load($folder . '/.env.test');
        $dbManager  = getenv('DB_MANAGER');

        switch($dbManager)
        {
            case 'mysql':
                {
                    $this->runCommand('d:database:import '.$databasePath);
                }
                break;
            case 'sqlite':
                {
                    $file_in  = $databasePath;
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
                    if(!is_dir($databasePath))
                    {
                        throw new TestDatabaseNotFoundException("$databasePath is not a valid test database or does not exist.", 1);
                    }

                    shell_exec('mongorestore --drop -d'.getenv('MONGODB_DB').' '.$databasePath. ' 2>&1');
                }
                break;
            default: throw new TestDatabaseNotFoundException("$dbManager is not handled. Valid database managers are 'mysql', 'mongodb' and 'sqlite'.", 1);
                break;
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