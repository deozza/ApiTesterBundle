<?php
namespace Deozza\PhilarmonyApiTesterBundle\Service\Fixtures;

use Symfony\Component\Yaml\Yaml;


class FixturesSchemaLoader
{

    public function __construct(string $path)
    {
        $this->fixturesPath = $path."/src/Fixtures";
    }

    public function loadFixtures()
    {
        foreach(glob($this->fixturesPath."/*Fixture.yaml") as $file)
        {
            $fixture = file_get_contents($file);

            try
            {
                $values = Yaml::parse($fixture);
            }
            catch(\Exception $e)
            {
                throw new \Exception($e->getMessage());
            }

            $this->executeFixture($values);
        }
    }

    private function executeFixture(array $fixture)
    {
        var_dump($fixture);
    }
}