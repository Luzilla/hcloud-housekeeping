#!/usr/bin/env php
<?php
namespace Luzilla\HCloud\Housekeeping;

require 'vendor/autoload.php';

$apiKey = getenv('HCLOUD_TOKEN');
if (empty($apiKey)) {
    die('Missing in environment: HCLOUD_TOKEN');
}

echo "Knock, knock!" . PHP_EOL;
sleep(1);
echo "This is Hetzner Cloud housekeeping!" . PHP_EOL . PHP_EOL;

$hetznerClient = new \LKDev\HetznerCloud\HetznerAPIClient($apiKey);

$servers = $hetznerClient->servers()->all();
$volumes = $hetznerClient->volumes()->all();
$keys    = $hetznerClient->sshkeys()->all();

echo sprintf(
    "Servers: %d\t\tVolumes: %d\t\tKeys: %d" . PHP_EOL . PHP_EOL,
    count($servers), count($volumes), count($keys)
);

foreach ($servers as $server) {
    echo "Deleting server";
    $server->delete();
    echo "   OK" . PHP_EOL;
}

foreach ($volumes as $volume) {
    echo "Deleting {$volume->name}..." . PHP_EOL;
    if ($volume->server) {
        echo "   Volume is attached to a server: DETACH." . PHP_EOL;
        $volume->detach();
        sleep(1);

        echo "   Deleting server." . PHP_EOL;

        volume_had_server:
            try {
                $server = $hetznerClient->servers()->get($volume->server);
                $server->delete();
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->hasResponse()) {
                    if ($e->getResponse()->getStatusCode() == 423) {
                        // server is locked
                        goto volume_had_server;
                    }
                }
            }
    }

    $volume->delete();
    echo "   OK" . PHP_EOL;
}

foreach($keys as $key) {
    if (strstr($key->name, 'molecule-generated') !== false) {
        echo "Deleting key {$key->name}: OK" . PHP_EOL;
        $key->delete();
    }
}
