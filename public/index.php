<?php declare(strict_types=1);

use Core\Bootstrap;

if (!defined('APP_START')) {
    define('APP_START', time());
}

if (file_exists($maintenance = __DIR__ . '/../storage/maintenance.php')) {
    require $maintenance;
}

require_once __DIR__ . '/../vendor/autoload.php';

try {
    Bootstrap::run();
} catch (Throwable $exception) {
    Bootstrap::setupExceptions();
}