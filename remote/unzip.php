<?php
declare(strict_types = 1);

$scriptDir = realpath('SCRIPT_DIR');
$deployDir = realpath('DEPLOY_DIR');

$dists = glob($scriptDir . '/dist/dist*.zip');
foreach ($dists as $dist) {
    $version = basename($dist, ".zip");
    $path = $deployDir . '/' . $version;

    if (is_dir($path)) {
        echo "Skipping " . $dist . "\n";
        continue;
    }
    echo "Deploying " . $dist . " to " . $path . " ...";
    $zip = new ZipArchive;
    $zip->open($dist);
    $res = $zip->extractTo($path);
    if ($res === false) {
        echo "Failed\n";
    } else echo "Done\n";
    $zip->close();
}
