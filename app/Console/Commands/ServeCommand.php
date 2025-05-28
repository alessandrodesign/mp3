<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    'serve',
    'Inicia o servidor embutido do PHP',
    ['serve']
)]
class ServeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host para o servidor', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Porta para o servidor', '8084');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = $input->getOption('host');
        $port = $input->getOption('port');

        $publicDir = realpath(PATH_PUBLIC);
        if ($publicDir === false) {
            $output->writeln('<error>Diretório public não encontrado.</error>');
            return Command::FAILURE;
        }

        $output->writeln("Iniciando servidor embutido do PHP em http://$host:$port");

        // Monta o comando para rodar o servidor embutido com router.php
        $router = realpath(PATH_ROOT . 'router.php');
        if ($router === false) {
            $output->writeln('<error>Arquivo router.php não encontrado.</error>');
            return Command::FAILURE;
        }

        $cmd = sprintf('php8 -S %s:%s -t %s %s', $host, $port, $publicDir, $router);

        // Executa o comando e mantém o processo rodando
        passthru($cmd);

        return Command::SUCCESS;
    }
}