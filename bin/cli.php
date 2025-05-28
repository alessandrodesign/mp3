<?php
// chmod +x cli.php

require __DIR__ . '/../vendor/autoload.php';

try {
    \Core\Bootstrap::run();
    $application = new \Symfony\Component\Console\Application('NorteDev Framework', '1.0.0');

    $commandsDir = __DIR__ . '/../app/Console/Commands';

    foreach (glob($commandsDir . '/*.php') as $commandFile) {
        $className = '\\App\\Console\\Commands\\' . basename($commandFile, '.php');

        if (class_exists($className)) {
            $application->add(new $className());
        }
    }

    $application->run();
} catch (Throwable $e) {
    echo $e->getMessage();
}