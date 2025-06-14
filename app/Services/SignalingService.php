<?php

namespace App\Services;

use App\Services\Signaling\SignalingInterface;

readonly class SignalingService
{
    public function __construct(
        private SignalingInterface $signalingService
    )
    {
    }

    public function run(): void
    {
        $this->signalingService->onConnect();
        $this->signalingService->onMessage();
        $this->signalingService->onClose();
        $this->signalingService->run();
    }

    public function runChat(): void
    {
        $this->signalingService->onConnectChat();
        $this->signalingService->onMessageChat();
        $this->signalingService->onCloseChat();
        $this->signalingService->online();
        $this->signalingService->run();
    }

    public function stop(): bool
    {
       return $this->signalingService->stop();
    }
}