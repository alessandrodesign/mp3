<?php

namespace App\Services\Signaling;

use Workerman\Worker;

class WorkermanWorker extends AbstractSignaling implements SignalingInterface
{
    private Worker $wsWorker;
    private array $clients = [];

    public function __construct()
    {
        $this->createPidFile(__CLASS__);
        $this->wsWorker = new Worker("websocket://0.0.0.0:8080");
    }

    public function onConnect(): void
    {
        $clients = $this->clients;

        $this->wsWorker->onConnect = function ($connection) use (&$clients) {
            $clients[$connection->id] = $connection;
            echo "Nova conexão: {$connection->id}\n";
        };

        $this->clients = $clients;
    }

    public function onMessage(): void
    {
        $clients = $this->clients;

        $this->wsWorker->onMessage = function ($connection, $data) use (&$clients) {
            foreach ($clients as $client_id => $client) {
                if ($client_id !== $connection->id) {
                    $client->send($data);
                }
            }
        };
    }

    public function onClose(): void
    {
        $clients = $this->clients;

        $this->wsWorker->onClose = function ($connection) use (&$clients) {
            unset($clients[$connection->id]);
            echo "Conexão {$connection->id} fechada\n";
        };

        $this->clients = $clients;
    }

    public function run(): void
    {
        $this->createPid();
        Worker::runAll();
    }
}