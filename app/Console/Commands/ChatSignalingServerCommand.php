<?php

namespace App\Console\Commands;

use App\Services\Signaling\WorkermanWorker;
use App\Services\SignalingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    'signaling:chat',
    'Inicia o servidores de sinalização WebSocket para Chats',
    ['signaling:chat']
)]
class ChatSignalingServerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);

        $output->writeln('Iniciando servidores de sinalização...');

        $signalingService = new SignalingService(new WorkermanWorker());
        $signalingService->runChat();

        return Command::SUCCESS;
    }
}