<?php

namespace App\Providers;

use App\Middlewares\ResponseCacheMiddleware;
use App\Services\Cache\ArrayCache;
use App\Services\Cache\FileCache;
use App\Services\Cache\RedisCache;
use App\Services\Translations\I18n;
use App\Services\Translations\I18nService;
use Core\Contracts\CacheInterface;
use Core\Utils\Directories;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use function DI\autowire;
use function DI\create;
use function DI\factory;

return [
    CacheInterface::class => factory(function () {
        return new FileCache();
//        return new ArrayCache();
//        return new RedisCache();
    }),

    LoggerInterface::class => function () {
        $logger = new Logger('app');
        $logPath = PATH_LOG . date('Ymd');
        Directories::validAndCreate($logPath);
        $logger->pushHandler(new StreamHandler($logPath . DS . 'app.log', Logger::DEBUG));
        return $logger;
    },

//    ResponseCacheMiddleware::class => \DI\autowire()->constructorParameter('ttl', 120),

    I18n::class => autowire(),
    I18nService::class => autowire(),
];