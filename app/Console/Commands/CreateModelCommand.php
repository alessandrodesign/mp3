<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    'make:model',
    'Cria um novo model Eloquent',
    ['make:model']
)]
class CreateModelCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nome do model');
        $this->addOption('table', null, InputOption::VALUE_OPTIONAL, 'Nome da tabela (opcional)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        $table = $input->getOption('table') ?? strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)) . 's';

        if (!str_ends_with($name, 'Model')) {
            $name .= 'Model';
        }

        $output->writeln("Criando model: $name");

        $stubContent = file_get_contents(__DIR__ . '/stubs/model.stub');

        $modelContent = str_replace(['{{class}}', '{{table}}'], [$name, $table], $stubContent);

        $filename = PATH_MODElS . "{$name}.php";

        file_put_contents($filename, $modelContent);

        $output->writeln("Model criado com sucesso em: $filename");

        return Command::SUCCESS;
    }
}