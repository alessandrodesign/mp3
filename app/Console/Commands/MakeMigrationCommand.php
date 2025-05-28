<?php

namespace App\Console\Commands;

use Core\Database;
use Core\Utils\Directories;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    'make:migration',
    'Cria um arquivo de migration',
    ['make:migration']
)]
class MakeMigrationCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nome da migration (ex: create_users_table)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $timestamp = date('Ymd_His');

        $dir = PATH_MIGRATIONS;
        Directories::validAndCreate($dir, 0755);

        $filename = $dir . "{$timestamp}_{$name}.php";

        if (file_exists($filename)) {
            $output->writeln("<error>Migration já existe: $filename</error>");
            return Command::FAILURE;
        }

        $output->writeln("Criando migration: $name");

        $modelContent = file_get_contents(__DIR__ . '/stubs/migration.stub');

        file_put_contents($filename, $modelContent);

        $output->writeln("Migration criado com sucesso em: $filename");

        return Command::SUCCESS;
    }
}