<?php

namespace Core;

use App\Middlewares\LocaleMiddleware;
use App\Middlewares\ResponseCacheMiddleware;
use Core\Routing\Router;
use Core\Utils\ClassFinder;
use Core\Utils\Directories;
use DI\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use RuntimeException;
use Exception;
use Throwable;

class App
{

    /**
     * Instância única do App.
     *
     * @var $instance static|null
     */
    private static ?self $instance = null;
    private ContainerBuilder $container;

    /**
     * Construtor privado para evitar instância direta.
     * @throws Exception
     */
    private function __construct()
    {
        $this->container = new ContainerBuilder();
        $this->container->useAutowiring(true);
        $this->container->useAttributes(true);

        if (Bootstrap::isProd()) {
            $cacheDir = PATH_CACHE . 'php-di' . DS . 'container';
            Directories::validAndCreate($cacheDir);
            $this->container->enableCompilation($cacheDir);
            $this->container->enableDefinitionCache();
            $proxiesDir = PATH_CACHE . 'php-di' . DS . 'proxies';
            Directories::validAndCreate($proxiesDir);
            $this->container->writeProxiesToFile(true, $proxiesDir);
        }

        $this->registerServices();
    }

    public static function start(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Retorna a instância atual.
     *
     * @return static
     * @throws RuntimeException
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException("App not initialized.");
        }

        return self::$instance;
    }

    public function getContainer(): ContainerBuilder
    {
        return $this->container;
    }

    private function registerServices(): void
    {
        // Registrar Request
        $this->container->addDefinitions([
            Request::class => \DI\factory(function () {
                $request = Request::createFromGlobals();
                $request->setLocale(DEFAULT_LOCALE);
                return $request;
            })
        ]);

        // Registrar Providers automaticamente
        $providersDir = PATH_PROVIDERS;
        Directories::validAndCreate($providersDir);
        $this->registerAutomaticallyDefinitios($providersDir);

        // Registrar Models automaticamente
        $modelsDir = PATH_MODElS;
        Directories::validAndCreate($modelsDir);
        $this->registerClassesAutomatically($modelsDir, MODELS_NAMESPACE);

        // Registrar Services automaticamente
        $servicesDir = PATH_SERVICES;
        Directories::validAndCreate($servicesDir);
        $this->registerClassesAutomatically($servicesDir, SERVICES_NAMESPACE);

        // Registrar Middlewares automaticamente
        $middlewaresDir = PATH_MIDDLEWARES;
        Directories::validAndCreate($middlewaresDir);
        $this->registerClassesAutomatically($middlewaresDir, MIDDLEWARES_NAMESPACE);

        // Registrar Controllers automaticamente
        $controllerClasses = PATH_CONTROLLERS;
        Directories::validAndCreate($controllerClasses);
        $this->registerClassesAutomatically($controllerClasses, CONTROLLERS_NAMESPACE);
    }

    private function registerClassesAutomatically(string $directory, string $baseNamespace): void
    {
        $classes = ClassFinder::findConcreteClasses($directory, $baseNamespace);

        foreach ($classes as $class) {
            $this->container->addDefinitions([
                $class => \DI\autowire($class)
            ]);
        }
    }

    private function registerAutomaticallyDefinitios(string $directory): void
    {
        $files = Directories::findFiles($directory);
        if (!empty($files)) {
            foreach ($files as $file) {
                $this->container->addDefinitions($file);
            }
        }
    }

    public function getId(string $class): string
    {
        $class = trim($class);
        $class = ltrim($class, '\\');
        $class = str_replace(["\\", "/"], "_", $class);
        return strtolower($class);
    }

    /**
     * Executa o sistema
     *
     * @throws Throwable
     */
    public function run(): void
    {
        $request = $this->container->build()->get(Request::class);

        $router = new Router($this->container);

        // Diretório e namespace base dos controllers
        $controllerClasses = Directories::listClasses(PATH_CONTROLLERS, CONTROLLERS_NAMESPACE);

        // Registra todos os controllers encontrados
        $router->registerControllers($controllerClasses);

        // Registrar middlewares globais
        $router->registerGlobalMiddlewares([
            ResponseCacheMiddleware::class,
            LocaleMiddleware::class,
        ]);

        $this->loadHelpers();

        $response = $router->dispatch($request);

        $response->send();
    }

    /**
     * @throws Exception
     */
    public function loadHelpers(): void
    {
        $helpersPath = PATH_HELPERS;
        $files = Directories::findFiles($helpersPath);

        if (empty($files)) {
            return;
        }

        global $container;
         $container= $this->container->build();

        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            include_once $file;
        }
    }
}