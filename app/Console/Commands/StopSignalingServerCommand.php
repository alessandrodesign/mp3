<?php

namespace App\Console\Commands;

use App\Services\Signaling\WorkermanWorker;
use App\Services\SignalingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    'signaling:stop',
    'Para o servidor de sinalização WebSocket',
    ['signaling:stop']
)]
class StopSignalingServerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Parando servidor de sinalização...');

        if (!(new SignalingService(new WorkermanWorker()))->stop()) {
            $output->writeln('<error>Erro ao parar servidor de sinalização.</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}