<?php

namespace App\Services\Signaling;

use SplObjectStorage;
use Workerman\Worker;

class WorkermanWorker extends AbstractSignaling implements SignalingInterface
{
    private Worker $wsWorker;
    private array $clients = [];
    private array $userConnections = [];
    private array $rooms = [];

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

    public function onConnectChat(): void
    {
        $userConnections = $this->userConnections;
        $rooms = $this->rooms;

        $this->wsWorker->onConnect = function ($connection) use (&$rooms, &$userConnections) {
            $connection->room = null;
            $connection->userId = null;
            echo "Nova conexão: {$connection->id}\n";
        };

        $this->userConnections = $userConnections;
        $this->rooms = $rooms;
    }

    public function onMessageChat(): void
    {
        $userConnections = $this->userConnections;
        $rooms = $this->rooms;

        $this->wsWorker->onMessage = function ($connection, $data) use (&$rooms, &$userConnections) {
            $message = json_decode($data, true);
            if (!$message) {
                $connection->send(json_encode(['error' => 'Mensagem inválida']));
                return;
            }

            switch ($message['type'] ?? '') {
                case 'join_room':
                    $roomId = $message['roomId'] ?? null;
                    $userId = $message['userId'] ?? 'anon';

                    if (!$roomId) {
                        $connection->send(json_encode(['error' => 'roomId é obrigatório']));
                        return;
                    }

                    if ($connection->room && isset($rooms[$connection->room])) {
                        $rooms[$connection->room]->detach($connection);
                        // Notifica saída
                        foreach ($rooms[$connection->room] as $client) {
                            $client->send(json_encode([
                                'type' => 'user_disconnected',
                                'userId' => $connection->userId,
                                'room' => $connection->room,
                            ]));
                        }
                    }

                    $connection->room = $roomId;
                    $connection->userId = $userId;

                    if (!isset($rooms[$roomId])) {
                        $rooms[$roomId] = new \SplObjectStorage();
                    }
                    $rooms[$roomId]->attach($connection);

                    // Notifica entrada
                    foreach ($rooms[$roomId] as $client) {
                        $client->send(json_encode([
                            'type' => 'user_connected',
                            'userId' => $userId,
                            'room' => $roomId,
                        ]));
                    }

                    $connection->send(json_encode(['info' => "Entrou na sala pública $roomId"]));
                    break;

                case 'join_private':
                    $user1 = $message['user1'] ?? null;
                    $user2 = $message['user2'] ?? null;
                    $userId = $message['userId'] ?? null;

                    if (!$user1 || !$user2 || !$userId) {
                        $connection->send(json_encode(['error' => 'user1, user2 e userId são obrigatórios']));
                        return;
                    }

                    if ($connection->room && isset($rooms[$connection->room])) {
                        $rooms[$connection->room]->detach($connection);
                        foreach ($rooms[$connection->room] as $client) {
                            $client->send(json_encode([
                                'type' => 'user_disconnected',
                                'userId' => $connection->userId,
                                'room' => $connection->room,
                            ]));
                        }
                    }

                    $connection->userId = $userId;
                    $privateRoom = 'private_' . min($user1, $user2) . '_' . max($user1, $user2);
                    $connection->room = $privateRoom;

                    if (!isset($rooms[$privateRoom])) {
                        $rooms[$privateRoom] = new \SplObjectStorage();
                    }
                    $rooms[$privateRoom]->attach($connection);

                    foreach ($rooms[$privateRoom] as $client) {
                        $client->send(json_encode([
                            'type' => 'user_connected',
                            'userId' => $userId,
                            'room' => $privateRoom,
                        ]));
                    }

                    $connection->send(json_encode(['info' => "Entrou na sala privada entre $user1 e $user2"]));
                    break;

                case 'message':
                    $text = $message['text'] ?? '';
                    if (!$connection->room) {
                        $connection->send(json_encode(['error' => 'Você não está em nenhuma sala']));
                        return;
                    }
                    $payload = [
                        'type' => 'message',
                        'from' => $connection->userId ?? 'anon',
                        'text' => $text,
                        'room' => $connection->room,
                        'timestamp' => time(),
                    ];
                    foreach ($rooms[$connection->room] as $client) {
                        $client->send(json_encode($payload));
                    }
                    break;

                case 'typing':
                    if (!$connection->room) return;
                    $payload = [
                        'type' => 'typing',
                        'from' => $connection->userId ?? 'anon',
                        'room' => $connection->room,
                    ];
                    foreach ($rooms[$connection->room] as $client) {
                        if ($client !== $connection) {
                            $client->send(json_encode($payload));
                        }
                    }
                    break;

                default:
                    $connection->send(json_encode(['error' => 'Tipo de mensagem desconhecido']));
                    break;
            }
        };

        $this->userConnections = $userConnections;
        $this->rooms = $rooms;
    }

    public function onCloseChat(): void
    {
        $userConnections = $this->userConnections;
        $rooms = $this->rooms;

        $this->wsWorker->onClose = function ($connection) use (&$rooms, &$userConnections) {
            echo "Conexão {$connection->id} fechada\n";
            if ($connection->room && isset($rooms[$connection->room])) {
                $rooms[$connection->room]->detach($connection);
                foreach ($rooms[$connection->room] as $client) {
                    $client->send(json_encode([
                        'type' => 'user_disconnected',
                        'userId' => $connection->userId,
                        'room' => $connection->room,
                    ]));
                }
            }
            if ($connection->userId && isset($userConnections[$connection->userId])) {
                unset($userConnections[$connection->userId]);
            }
        };

        $this->userConnections = $userConnections;
        $this->rooms = $rooms;
    }

    public function online(): void
    {
        foreach ($this->wsWorker->connections as $conn) {
            $conn->send(json_encode(['type' => 'server_online']));
        };
    }
}