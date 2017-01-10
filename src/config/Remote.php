<?php
declare(strict_types = 1);

namespace mheinzerling\dist\config;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\FtpConnection;
use mheinzerling\commons\JsonUtils;
use mheinzerling\commons\Separator;
use mheinzerling\commons\StringUtils;

class Remote
{
    /**
     * @var Ftp
     */
    private $ftp;
    /**
     * @var string
     */
    private $root;
    /**
     * @var string
     */
    private $deployDir;
    /**
     * @var string
     */
    private $scriptDir;

    /**
     * @var string
     */
    private $authuser;
    /**
     * @var string
     */
    private $authpwd;
    /**
     * @var string
     */
    private $htaccess;
    /**
     * @var string
     */
    private $maintenanceAgent;
    /**
     * @var string
     */
    private $urlRoot;
    /**
     * @var string|null
     */
    private $path;
    /**
     * @var array
     */
    private $callbacks;


    public function __construct(array $json)
    {
        JsonUtils::validProperties($json, ["ftp", "root", "deployDir", "scriptDir", "authuser", "authpwd", "htaccess", "url", "path", "maintenance", "callback"]);
        $this->ftp = new Ftp(JsonUtils::required($json, 'ftp'));
        $this->root = rtrim(FileUtils::to(JsonUtils::required($json, "root"), Separator::UNIX()), Separator::UNIX) . Separator::UNIX;;

        //with trailing / and unix separator TODO
        $this->deployDir = rtrim(FileUtils::to(JsonUtils::required($json, "deployDir"), Separator::UNIX()), Separator::UNIX) . Separator::UNIX;
        $this->scriptDir = rtrim(FileUtils::to(JsonUtils::required($json, "scriptDir"), Separator::UNIX()), Separator::UNIX) . Separator::UNIX;

        //in root
        $this->htaccess = JsonUtils::optional($json, "htaccess", "");
        // for script dir
        $this->authuser = JsonUtils::optional($json, "authuser");
        $this->authpwd = JsonUtils::optional($json, "authpwd");

        $this->maintenanceAgent = JsonUtils::optional($json, "maintenance", "");

        $this->urlRoot = JsonUtils::required($json, "url");
        $this->path = JsonUtils::optional($json, "path");

        $callback = JsonUtils::optional($json, "callback", []);
        $this->callbacks['afterDeploy'] = [];
        $afterDeploy = JsonUtils::optional($callback, "afterDeploy", []);
        foreach ($afterDeploy as $json) {
            $this->callbacks['afterDeploy'][] = new UrlCallback($json);
        }
    }

    public function createFtpConnection(): FtpConnection
    {
        return $this->ftp->createConnection();
    }

    public function getScriptFile($file)
    {
        return FileUtils::append($this->getScriptDirectory(), $file);
    }

    public function getScriptDirectory(): string
    {
        return $this->scriptDir;
    }

    public function getArchiveDirectory(): string
    {
        return FileUtils::append($this->getScriptDirectory(), "dist");
    }

    public function getAbsoluteScriptDirectory(): string
    {
        return FileUtils::append($this->root, $this->scriptDir);
    }

    public function getAbsoluteScriptFile($file)
    {
        return FileUtils::append($this->getAbsoluteScriptDirectory(), $file);
    }

    public function getAbsoluteArchiveDirectory(): string
    {
        return FileUtils::append($this->root, $this->getArchiveDirectory());
    }

    /**
     * @return string[]
     */
    public function getArchives(): array
    {
        $ftp = $this->createFtpConnection();
        return $ftp->ls($this->getArchiveDirectory(), '@dist.*\.zip@', true);
    }

    public function getArchiveFile($version)
    {
        $distName = 'dist.' . $version . '.zip';
        return FileUtils::append($this->getArchiveDirectory(), $distName);
    }

    public function getExtractedDirectories()
    {
        $ftp = $this->createFtpConnection();
        return $ftp->ls($this->getDeployDirectory(), '@dist.*@', true);
    }


    public function getDeployDirectory(): string
    {
        return $this->deployDir;
    }

    public function getAbsoluteDeployDirectory(): string
    {
        return FileUtils::append($this->root, $this->getDeployDirectory());
    }

    /**
     * @return string[]
     */
    public function getScriptReplacement(): array
    {
        return [
            'SCRIPT_DIR' => $this->getAbsoluteScriptDirectory(),
            'DEPLOY_DIR' => $this->getAbsoluteDeployDirectory(),
            'AUTH_USER_FILE' => $this->getAbsoluteScriptFile(".htpasswd"),
            'HTACCESS' => $this->htaccess,
            'USER' => $this->authuser,
            'PWD' => password_hash($this->authpwd, PASSWORD_BCRYPT) //TODO move to json
        ];
    }


    public function getHtaccess(): string
    {
        return trim(FileUtils::append($this->getDeployDirectory(), ".htaccess"), "/");
    }

    public function currentVersion(): string
    {
        $ftp = $this->createFtpConnection();
        $content = $ftp->get($this->getHtaccess());
        if ($content != null) {
            return preg_replace("@.*(dist\\..*)/.*@ism", "\\1", $content);
        } else {
            return "None";
        }
    }

    /**
     * @param string $method
     * @return resource
     */
    public function createBasicAuthContext(string $method)
    {
        return self::createContext($this->authuser, $this->authpwd, $this->maintenanceAgent, $method);
    }


    public function getUnzipUrl(): string
    {
        $path = "http://" . $this->urlRoot;
        $path = FileUtils::append($path, $this->path);
        return FileUtils::append(FileUtils::append($path, $this->getScriptDirectory()), "unzip.php");
    }

    public function unzip()
    {
        return file_get_contents($this->getUnzipUrl(), false, $this->createBasicAuthContext("GET"));
    }

    public function getHtaccessReplacement($directory): array
    {
        return [
            'VERSION' => FileUtils::append($this->getDeployDirectory(), $directory),
            '/PATH' => StringUtils::isBlank($this->path) ? "" : ("/" . $this->path)
        ];
    }

    /**
     * @return UrlCallback[]
     */
    public function afterDeploy(): array
    {
        return $this->callbacks['afterDeploy'];
    }

    /**
     * @param string|null $user
     * @param string|null $password
     * @param string|null $userAgent
     * @param string|null $method
     * @return resource
     */
    public static function createContext(?string $user, ?string $password, ?string $userAgent, ?string $method)
    {
        $opts = [];
        $opts['http'] = [];
        $opts['http']['timeout'] = 60;
        $opts['http']['content'] = '';
        if ($userAgent != null) $opts['http']['user_agent'] = $userAgent;
        if ($method != null) $opts['http']['method'] = $method;
        if ($user != null) $opts['http']['header'] = "Content-Type: text/html\r\n" .
            "Authorization: Basic " . base64_encode($user . ":" . $password) . "\r\n";
        return stream_context_create($opts);
    }
}
