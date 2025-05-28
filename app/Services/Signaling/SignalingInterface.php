<?php

namespace App\Services\Signaling;

interface SignalingInterface
{
    public function onConnect(): void;

    public function onConnectChat(): void;

    public function onMessage(): void;

    public function onMessageChat(): void;

    public function onClose(): void;

    public function onCloseChat(): void;

    public function online(): void;

    public function run(): void;

    public function stop(): bool;
}