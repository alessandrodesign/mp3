<?php

namespace App\Console\Commands;

use Core\Database;
use Core\Utils\Directories;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    'migrate',
    'Executa todas as migrations pendentes',
    ['migrate']
)]
class MigrateCommand extends Command
{
    public function __construct(?string $name = null)
    {
        Database::getInstance()->configureMigrations();
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = PATH_MIGRATIONS;
        Directories::validAndCreate($dir, 0755);
        $files = glob($dir . '/*.php');
        sort($files);
        foreach ($files as $file) {
            $className = require_once $file;
            $migration = new $className();
            $name = basename($file);

            if (Database::getInstance()->isMigrated($name)) {
                $output->writeln("<comment>Migration $migrationName já aplicada, pulando.</comment>");
                continue;
            }

            if (!method_exists($migration, 'up')) {
                $output->writeln("<error>Não foi possível identificar up() na migration $name</error>");
                continue;
            }

            $migration->up();

            Database::getInstance()->migrated($name);

            $output->writeln("Executando up() da migration " . $name . "...");
        }

        $output->writeln('<info>Migrations executadas com sucesso.</info>');
        return Command::SUCCESS;
    }
}