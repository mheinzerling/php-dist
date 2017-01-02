<?php
declare(strict_types = 1);

namespace mheinzerling\dist\config;


use mheinzerling\commons\JsonUtils;

class UrlCallback
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var resource
     */
    private $context;

    public function __construct(array $json)
    {
        JsonUtils::validProperties($json, ["url", "method", "agent", "authuser", "authpwd"]);
        $this->url = JsonUtils::required($json, 'url');
        $method = JsonUtils::optional($json, 'method', 'GET');
        $agent = JsonUtils::optional($json, 'agent');
        $authuser = JsonUtils::optional($json, 'authuser');
        $authpwd = JsonUtils::optional($json, 'authpwd');

        $this->context = Remote::createContext($authuser, $authpwd, $agent, $method);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return resource
     */
    public function getContext(): resource
    {
        return $this->context;
    }


}