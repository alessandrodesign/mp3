<?php

namespace Core;

use Illuminate\Support\Collection;
use RuntimeException;
use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    private Capsule $capsule;
    /**
     * Instância única do Bootstrap.
     *
     * @var $instance self|null
     */
    private static ?self $instance = null;

    private function __construct()
    {
        $this->defineConnection();
    }

    /**
     * Retorna a instância atual.
     *
     * @return self
     * @throws RuntimeException
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function configure(): self
    {
        return self::getInstance();
    }

    private function defineConnection(): void
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => DB_DRIVER,
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS,
            'port' => DB_PORT,
            'charset' => DB_CHARSET,
            'collation' => DB_COLLATION,
            'prefix' => DB_PREFIX,
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->capsule = $capsule;
    }

    public static function Capsule(): Capsule
    {
        return self::getInstance()->capsule;
    }

    public function configureMigrations(): void
    {
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->string('migration')->primary();
                $table->timestamp('batch_at')->useCurrent();
            });
        }
    }

    public function isMigrated($migrationName): bool
    {
        return Capsule::table('migrations')->where('migration', $migrationName)->exists();
    }

    public function migrated($migrationName): void
    {
        Capsule::table('migrations')->insert([
            'migration' => $migrationName,
            'batch_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function lastBatch()
    {
        return Capsule::table('migrations')->max('batch_at');
    }

    public function migrationsByLastBatch($lastBatch): Collection
    {
        return Capsule::table('migrations')
            ->where('batch_at', $lastBatch)
            ->orderByDesc('migration')
            ->get();
    }

    public function deleteMigration($migration): int
    {
        return Capsule::table('migrations')->where('migration', $migration)->delete();
    }
}