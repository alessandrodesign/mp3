<?php

require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

$ws_worker = new Worker("ws://0.0.0.0:2346");
$ws_worker->count = 1;

$clients = [];

$ws_worker->onConnect = function (TcpConnection $conn) use (&$clients) {
    echo "Nova conexão: {$conn->id}\n";
    $clients[$conn->id] = ['conn' => $conn, 'role' => null];
};

$ws_worker->onMessage = function (TcpConnection $conn, $data) use (&$clients) {
    $msg = json_decode($data, true);
    if (!$msg || !isset($msg['type'])) return;

    switch ($msg['type']) {
        case 'role':
            $clients[$conn->id]['role'] = $msg['role'];
            echo "Cliente {$conn->id} é um {$msg['role']}\n";
            break;

        case 'offer':
        case 'answer':
        case 'candidate':
            foreach ($clients as $id => $client) {
                if ($id !== $conn->id && $client['role'] !== $clients[$conn->id]['role']) {
                    $client['conn']->send($data);
                }
            }
            break;
    }
};

$ws_worker->onClose = function ($conn) use (&$clients) {
    echo "Conexão encerrada: {$conn->id}\n";
    unset($clients[$conn->id]);
};

Worker::runAll();
