#!/usr/bin/env php
<?php
namespace Luzilla\HCloud\Housekeeping;

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

foreach($hetznerClient->sshkeys()->all() as $key) {
    if (strstr($key->name, 'molecule-generated') !== false) {
        echo "Deleting key {$key->name}: OK" . PHP_EOL;
        $key->delete();
    }
}
