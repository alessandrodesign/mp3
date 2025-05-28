<?php
// chmod +x cli.php


use Core\App;
use Core\Bootstrap;

require __DIR__ . '/../vendor/autoload.php';

Bootstrap::run();

$commands = [
    'help' => 'Exibe esta ajuda',
    'route:list' => 'Lista todas as rotas registradas',
    'cache:clear' => 'Limpa o cache da aplicação',
];

function printHelp(array $commands): void
{
    echo "Comandos disponíveis:\n";
    foreach ($commands as $cmd => $desc) {
        echo "  $cmd\t$desc\n";
    }
}

function listRoutes(): void
{
    $app = App::getInstance();
    $router = $app->getRouter(); // Supondo que tenha método getRouter()
    $routes = $router->getAllRoutes(); // Método que você deve implementar para retornar rotas

    echo "\nRotas registradas:\n";
    foreach ($routes as $method => $routesArray) {
        foreach ($routesArray as $route) {
            echo sprintf("  [%s] %s -> %s::%s\n", $method, $route['path'], $route['controller'], $route['method']);
        }
    }
}

function clearCache(): void
{
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        echo "Cache não encontrado.\n";
        return;
    }
    $files = glob($cacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "Cache limpo.\n";
}

$argv = $_SERVER['argv'];
$command = $argv[1] ?? 'help';

switch ($command) {
    case 'help':
        printHelp($commands);
        break;
    case 'route:list':
        listRoutes();
        break;
    case 'cache:clear':
        clearCache();
        break;
    default:
        echo "Comando desconhecido: $command\n\n";
        printHelp($commands);
        exit(1);
}