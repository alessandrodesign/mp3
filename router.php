<?php
// php -S 127.0.0.1:8084 router.php

// Caminho absoluto para a pasta pública (onde está o index.php)
$publicDir = __DIR__ . '/public';

// Caminho do arquivo solicitado
$requested = $publicDir . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Se o arquivo existe, serve ele diretamente
if (php_sapi_name() === 'cli-server' && is_file($requested)) {
    return false; // Deixa o servidor embutido servir o arquivo estático
}

// Caso contrário, redireciona para o front controller
require $publicDir . '/index.php';