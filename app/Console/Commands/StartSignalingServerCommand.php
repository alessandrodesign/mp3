<?php

namespace App\Console\Commands;

use App\Services\Signaling\WorkermanWorker;
use App\Services\SignalingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    'signaling:start',
    'Inicia o servidor de sinalização WebSocket',
    ['signaling:start']
)]
class StartSignalingServerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Iniciando servidor de sinalização...');

        $signalingService = new SignalingService(new WorkermanWorker());
        $signalingService->run();

        return Command::SUCCESS;
    }
}