<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateMiddlewareCommand extends Command
{
    protected static $defaultName = 'make:middleware';
    protected static $defaultDescription = 'Cria um novo middleware';

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nome do middleware');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $output->writeln("Criando middleware: $name");

        if (!str_ends_with($name, 'Middleware')) {
            $name .= 'Middleware';
        }

        $stubContent = file_get_contents(__DIR__ . '/stubs/middleware.stub');
        $middlewareContent = str_replace('{{class}}', $name, $stubContent);

        $filename = PATH_MIDDLEWARES . "{$name}.php";
        file_put_contents($filename, $middlewareContent);

        $output->writeln("Middleware criado com sucesso em: $filename");

        return Command::SUCCESS;
    }
}