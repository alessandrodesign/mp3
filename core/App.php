<?php

namespace Core;

use Core\Routing\Router;
use Core\Utils\ClassFinder;
use Core\Utils\Directories;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use RuntimeException;
use Exception;

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
     */
    private function __construct()
    {
        $this->container = new ContainerBuilder();
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
        $this->container->register('request', Request::class)
            ->setFactory([Request::class, 'createFromGlobals']);

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

        // Compilar container para resolver autowiring
//        $this->container->compile();
    }

    private function registerClassesAutomatically(string $directory, string $baseNamespace): void
    {
        $classes = ClassFinder::findConcreteClasses($directory, $baseNamespace);

        foreach ($classes as $class) {
            if (!$this->container->has($class)) {
                $this->container->register($class, $class)
                    ->setAutowired(true)
                    ->setPublic(true)
                    ->setLazy(true);
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
     * @throws Exception
     */
    public function run(): void
    {
        $request = $this->container->get('request');

        $router = new Router($this->container);

        // Diretório e namespace base dos controllers
        $controllerClasses = Directories::listClasses(PATH_CONTROLLERS, CONTROLLERS_NAMESPACE);

        // Registra todos os controllers encontrados
        $router->registerControllers($controllerClasses);

        // Registrar middlewares globais, se houver
        // $router->registerGlobalMiddlewares([
        //     Middleware\SomeGlobalMiddleware::class,
        // ]);

        $response = $router->dispatch($request);
        $response->send();
    }
}