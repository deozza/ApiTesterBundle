<?php

namespace Deozza\PhilarmonyApiTesterBundle\Command;

use Deozza\PhilarmonyApiTesterBundle\Service\Fixtures\FixturesSchemaLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixturesCommand extends Command
{
    protected static $defaultName = 'philarmony:fixtures:load';

    public function __construct(FixturesSchemaLoader $fixturesSchemaLoader)
    {
        $this->fixturesSchemaLoader = $fixturesSchemaLoader;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription("Update your system based on your migrations");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fixturesSchemaLoader->loadFixtures();
    }

}