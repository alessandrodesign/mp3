<?php
// chmod +x cli.php

require __DIR__ . '/../vendor/autoload.php';

try {
    \Core\Bootstrap::run();

    $application = new \Symfony\Component\Console\Application('NorteDev Framework', '1.0.0');

//    // Diretório onde seus comandos estão localizados
//    $commandsDir = __DIR__ . '/../app/Console/Commands';
//
//    // Carrega todos os arquivos PHP do diretório de comandos
//    foreach (glob($commandsDir . '/*.php') as $commandFile) {
//        // Obtém o nome da classe baseado no namespace e nome do arquivo
//        $className = '\\App\Console\\Commands\\' . basename($commandFile, '.php');
//
//        if (class_exists($className)) {
//            $application->add(new $className());
//        }
//    }
    $application->add(new \App\Console\Commands\CreateControllerCommand());
    $application->add(new \App\Console\Commands\CreateMiddlewareCommand());
    $application->add(new \App\Console\Commands\CreateModelCommand());
    $application->run();
} catch (Throwable $e) {
    echo $e->getMessage();
}