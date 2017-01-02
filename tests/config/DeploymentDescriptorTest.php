<?php
declare(strict_types = 1);

namespace mheinzerling\dist\config;


class DeploymentDescriptorTest extends \PHPUnit_Framework_TestCase
{

    public function testLoad()
    {
        $config = DeploymentDescriptor::loadFile(realpath(__DIR__ . "/../..") . "/resources/deploy.json");
        var_dump($config); //TODO assert and parse errors
    }


}