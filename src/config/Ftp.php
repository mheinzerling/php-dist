<?php
declare(strict_types = 1);

namespace mheinzerling\dist\config;


use mheinzerling\commons\ExtensionFtpConnection;
use mheinzerling\commons\JsonUtils;

class Ftp
{
    /**
     * @var string
     */
    private $server;
    /**
     * @var string
     */
    private $user;
    /**
     * @var string
     */
    private $password;

    public function __construct(array $json)
    {
        JsonUtils::validProperties($json, ["server", "user", "password"]);
        $this->server = JsonUtils::required($json, "server");
        $this->user = JsonUtils::required($json, "user");
        $this->password = JsonUtils::required($json, "password");
    }

    public function createConnection()
    {
        //TODO Factory for test
        return new ExtensionFtpConnection($this->server, $this->user, $this->password);
    }
}