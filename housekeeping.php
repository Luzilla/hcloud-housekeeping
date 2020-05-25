#!/usr/bin/env php
<?php
require 'vendor/autoload.php';

$apiKey = getenv('HCLOUD_TOKEN');

echo "Knock, knock!" . PHP_EOL;
sleep(1);
echo "This is Hetzner Cloud housekeeping!" . PHP_EOL;

$hetznerClient = new \LKDev\HetznerCloud\HetznerAPIClient($apiKey);
foreach ($hetznerClient->servers()->all() as $server) {
    echo "Deleting server";
    $server->delete();
    echo "   OK" . PHP_EOL;
}

foreach ($hetznerClient->volumes()->all() as $volume) {
    echo "Deleting {$volume->name}..." . PHP_EOL;
    if ($volume->server) {
        echo "   Volume is attached to a server: DETACH." . PHP_EOL;
        $volume->detach();
        $hetznerClient->servers()->get($volume->server)->delete();
        echo "   Deleting server." . PHP_EOL;
    }
    $volume->delete();
    echo "   OK" . PHP_EOL;
}

foreach($hetznerClient->sshkeys()->all() as $key) {
    if (strstr($key->name, 'molecule-generated') != false) {
        echo "Deleting key {$key->name}: OK" . PHP_EOL;
        $key->delete();
    }
}
