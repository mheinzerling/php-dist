<?php
declare(strict_types = 1);

namespace mheinzerling\dist\config;


use mheinzerling\commons\JsonUtils;

class Zip
{
    /**
     * @var string[]
     */
    private $ignore;
    /**
     * @var string[]
     */
    private $expectedExtensions;

    public function __construct(array $json)
    {
        JsonUtils::validProperties($json, ["expectedExtensions", "ignore"]);
        $this->ignore = JsonUtils::optional($json, "ignore", []);
        $this->expectedExtensions = JsonUtils::required($json, "expectedExtensions");
    }

    public function ignore(string $localAbsolutePath): bool
    {
        foreach ($this->ignore as $filter) {
            if (strstr($localAbsolutePath, $filter) !== false) return true;
        }
        return false;
    }

    public function allowedExtension($file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return in_array($ext, $this->expectedExtensions);
    }
}