<?php

namespace App\Console\Commands;

use Core\Bootstrap;
use Core\Database;
use Core\Utils\Directories;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    'migrate:rollback',
    'Executa o método down() das migrations na ordem inversa',
    ['migrate:rollback']
)]
class MigrateRollbackCommand extends Command
{
    public function __construct(?string $name = null)
    {
        Database::getInstance()->configureMigrations();
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Confirmação se estiver em produção
        if (Bootstrap::isProd()) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                '<question>Você está em ambiente de produção. Tem certeza que deseja continuar com o rollback? (y/N) </question>',
                false
            );
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Rollback cancelado.</comment>');
                return Command::SUCCESS;
            }
        }

        $lastBatch = Database::getInstance()->lastBatch();
        if (!$lastBatch) {
            $output->writeln('<comment>Nenhuma migration aplicada ainda.</comment>');
            return Command::SUCCESS;
        }

        $migrations = Database::getInstance()->migrationsByLastBatch($lastBatch);

        if ($migrations->isEmpty()) {
            $output->writeln('<comment>Nenhuma migration para rollback.</comment>');
            return Command::SUCCESS;
        }

        $dir = PATH_MIGRATIONS;
        Directories::validAndCreate($dir, 0755);

        foreach ($migrations as $migrationRecord) {
            $file = $dir . $migrationRecord->migration;

            if (!file_exists($file)) {
                $output->writeln("<error>Arquivo da migration {$migrationRecord->migration} não encontrado.</error>");
                continue;
            }

            $className = require_once $file;
            $migration = new $className();
            $name = basename($file);
            if (!method_exists($migration, 'down')) {
                $output->writeln("<error>Não foi possível identificar down) na migration $name</error>");
                continue;
            }
            $output->writeln("Executando down() da migration " . $name . "...");
            $migration->down();

            if (method_exists($migrationRecord, 'delete')) {
                $migrationRecord->delete();
            } else {
                Database::getInstance()->deleteMigration($migrationRecord->migration);
            }

            $output->writeln("<info>Migration {$migrationRecord->migration} revertida com sucesso.</info>");
        }

        $output->writeln('<info>Migrations rollback executadas com sucesso.</info>');
        return Command::SUCCESS;
    }
}