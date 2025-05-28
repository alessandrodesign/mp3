<?php

namespace Core;

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
        $this->capsule = new Capsule;

        $this->capsule->addConnection([
            'driver' => DB_DRIVER,
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS,
            'charset' => DB_CHARSET,
            'collation' => DB_COLLATION,
            'prefix' => DB_PREFIX,
        ]);

        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    public static function Capsule(): Capsule
    {
        return self::getInstance()->capsule;
    }
}