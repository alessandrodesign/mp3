<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    'make:controller',
    'Cria um novo controller',
    ['make:controller']
)]
class CreateControllerCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nome do controller');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $output->writeln("Criando controller: $name");

        $stubContent = file_get_contents(__DIR__ . '/stubs/controller.stub');
        $controllerContent = str_replace('{{class}}', $name, $stubContent);

        $filename = PATH_CONTROLLERS . "{$name}.php";
        file_put_contents($filename, $controllerContent);

        $output->writeln("Controller criado com sucesso em: $filename");

        return Command::SUCCESS;
    }
}