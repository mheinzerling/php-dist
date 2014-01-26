<?php

namespace mheinzerling\commons;


class GitUtils
{
    public static function getVersion($annotated = false)
    {
        $cmd = "git describe --long --dirty=+";
        if (!$annotated) $cmd .= " --tags";
        $p = new Process($cmd);
        $p->run();
        $version = trim($p->getOut());
        if ($p->getErr() == "fatal: No names found, cannot describe anything.\n") {
            $version = "preinitial-0-g0000000";
        }
        if (strstr($p->getErr(), "fatal: Not a git repository")) {
            return null;
        }

        $branch = self::getCurrentBranch();
        if ($branch != "master") $version = preg_replace("@-(\\d+)-g@", '-' . $branch . '-\\1-g', $version);

        return $version;
    }

    public static function hasLocalChanges()
    {
        $p = new Process("git status");
        $p->run(true);
        if (strstr($p->getErr(), "fatal: Not a git repository")) {
            return null;
        }
        return stristr($p->getOut(), 'nothing to commit') === false;
    }

    public static function getCurrentBranch()
    {
        $p = new Process("git symbolic-ref --short HEAD");
        $p->run(true);
        if (strstr($p->getErr(), "fatal: Not a git repository")) {
            return null;
        }
        return trim($p->getOut());

    }
}