<?php

namespace App\Services\Signaling;

interface SignalingInterface
{
    public function onConnect(): void;

    public function onMessage(): void;

    public function onClose(): void;

    public function run(): void;

    public function stop(): bool;
}