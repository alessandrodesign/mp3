<?php declare(strict_types=1);

use Core\Bootstrap;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    Bootstrap::run();
} catch (Throwable $exception) {
    Bootstrap::setupExceptions();
}