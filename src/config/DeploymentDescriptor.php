<?php
declare(strict_types = 1);

namespace mheinzerling\dist\config;


use mheinzerling\commons\JsonUtils;

class DeploymentDescriptor
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Remote|null
     */
    private $remote;
    /**
     * @var Local
     */
    private $local;
    /**
     * @var Zip|null
     */
    private $zip;

    private function __construct(array $json)
    {
        JsonUtils::validProperties($json, ["name", "remote", "local", "zip", "callback"]);
        $this->name = JsonUtils::required($json, 'name');
        $this->local = new Local(JsonUtils::required($json, 'local'));

        $remote = JsonUtils::optional($json, 'remote');
        if ($remote != null) $this->remote = new Remote($remote);

        $zip = JsonUtils::optional($json, 'zip');
        if ($zip != null) $this->zip = new Zip($zip);
    }

    public static function loadFile(string $file): DeploymentDescriptor
    {
        return self::loadJson(file_get_contents($file));
    }

    public static function loadJson(string $jsonString): DeploymentDescriptor
    {
        $json = JsonUtils::parseToArray($jsonString);
        return new DeploymentDescriptor($json);
    }

    public function local(): Local
    {
        return $this->local;
    }

    public function zip(): Zip
    {
        if ($this->zip == null) throw new \Exception("Missing 'zip' section in deployment descriptor to execute this command");
        return $this->zip;
    }

    public function remote(): Remote
    {
        if ($this->remote == null) throw new \Exception("Missing 'remote' section in deployment descriptor to execute this command");
        return $this->remote;
    }
}