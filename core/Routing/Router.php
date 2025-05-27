<?php

namespace Core\Routing;

use Core\App;
use Core\Contracts\MiddlewareInterface;
use DI\Container;
use DI\ContainerBuilder;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Router
{
    /**
     * Estrutura das rotas:
     * [
     *   'GET' => [
     *       [
     *         'regex' => string,
     *         'paramNames' => array,
     *         'controller' => string,
     *         'method' => string,
     *         'name' => ?string,
     *         'middlewares' => array
     *       ],
     *       ...
     *   ],
     *   ...
     * ]
     */
    private array $routes = [];
    private array $namedRoutes = [];
    private array $globalMiddlewares = [];
    private Container $container;

    /**
     * @throws Exception
     */
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container->build();
    }

    /**
     * Registra rotas a partir de uma lista de classes controller.
     *
     * @param string[] $controllerClasses
     * @throws ReflectionException
     */
    public function registerControllers(array $controllerClasses): void
    {
        foreach ($controllerClasses as $controllerClass) {
            $this->registerControllerRoutes($controllerClass);
        }
    }

    /**
     * Registra as rotas de um controller, convertendo paths com parâmetros em regex.
     *
     * @throws ReflectionException
     */
    private function registerControllerRoutes(string $controllerClass): void
    {
        $refClass = new ReflectionClass($controllerClass);
        foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isAbstract()) {
                continue;
            }
            $attributes = $method->getAttributes(Route::class);
            foreach ($attributes as $attribute) {
                /** @var Route $route */
                $route = $attribute->newInstance();

                foreach ($route->methods as $httpMethod) {
                    $paramNames = [];
                    $regex = $this->convertPathToRegex($route->path, $paramNames);

                    $this->routes[$httpMethod][] = [
                        'regex' => $regex,
                        'paramNames' => $paramNames,
                        'controller' => $controllerClass,
                        'method' => $method->getName(),
                        'name' => $route->name,
                        'middlewares' => $this->getMiddlewaresFromAttributes($method),
                    ];
                }

                if ($route->name !== null) {
                    $this->namedRoutes[$route->name] = $route->path;
                }
            }
        }
    }

    /**
     * Converte uma rota com parâmetros no formato /foo/{bar} para regex e extrai os nomes dos parâmetros.
     *
     * @param string $path
     * @param array $paramNames
     * @return string Regex para casar a rota
     */
    private function convertPathToRegex(string $path, array &$paramNames): string
    {
        $paramNames = [];
        $regex = preg_replace_callback('/\{(\w+)\}/', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            if ($matches[1] === 'path') {
                return '(.+)';
            }
            return '([^\/]+)';
        }, $path);

        return '#^' . $regex . '$#';
    }

    /**
     * Registra middlewares globais que serão executados em todas as rotas.
     *
     * @param string[] $middlewareClasses
     */
    public function registerGlobalMiddlewares(array $middlewareClasses): void
    {
        foreach ($middlewareClasses as $middlewareClass) {
            $this->globalMiddlewares[] = $middlewareClass;
        }
    }

    /**
     * Extrai middlewares definidos via atributo #[Middleware(...)] no método do controller.
     *
     * @param ReflectionMethod $method
     * @return string[] Lista de classes middleware
     */
    private function getMiddlewaresFromAttributes(ReflectionMethod $method): array
    {
        $middlewares = [];
        $attributes = $method->getAttributes(Middleware::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $middlewares[] = $instance->class;
        }
        return $middlewares;
    }

    /**
     * Despacha a requisição para o controller correto, executando middlewares e injetando parâmetros da rota.
     *
     * @throws Exception
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPathInfo();

        if (!isset($this->routes[$method])) {
            return new Response('Page not found', 404);
        }

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                $params = [];
                foreach ($route['paramNames'] as $index => $name) {
                    $params[$name] = $matches[$index];
                }

                $controllerClass = $route['controller'];
                $methodName = $route['method'];
                $routeMiddlewares = $route['middlewares'] ?? [];

                if (!class_exists($controllerClass)) {
                    return new Response("Controller {$controllerClass} not found", 500);
                }

                $controllerCallable = function (Request $request) use ($controllerClass, $methodName, $params) {

                    if (!$this->container->has($controllerClass)) {
                        return new Response("Controller {$controllerClass} not found", 500);
                    }

                    $controller = $this->container->get($controllerClass);

                    $refMethod = new ReflectionMethod($controllerClass, $methodName);
                    $args = [];
                    foreach ($refMethod->getParameters() as $param) {
                        $paramType = $param->getType();

                        if ($paramType && !$paramType->isBuiltin()) {
                            $paramClass = $paramType->getName();

                            if ($paramClass === Request::class) {
                                $args[] = $request;
                            } else {
                                if ($this->container->has($paramClass)) {
                                    $args[] = $this->container->get($paramClass);
                                } else {
                                    throw new RuntimeException("Dependency {$paramClass} not registered in container for injection in method {$methodName} of controller {$controllerClass}");
                                }
                            }
                        } else {
                            // Parâmetro escalar: tenta preencher com parâmetro da rota
                            if (array_key_exists($param->getName(), $params)) {
                                $args[] = $params[$param->getName()];
                            } elseif ($param->isDefaultValueAvailable()) {
                                $args[] = $param->getDefaultValue();
                            } else {
                                throw new RuntimeException("Scalar parameter '{$param->getName()}' in method {$methodName} of controller {$controllerClass} cannot be automatically resolved and has no default value.");
                            }
                        }
                    }

                    try {
                        $response = $refMethod->invokeArgs($controller, $args);

                        if ($response instanceof Response) {
                            return $response;
                        }

                        return new Response($response);
                    } catch (Throwable $e) {
                        if ($e->getCode()) {
                            return new Response($e->getMessage(), $e->getCode());
                        }
                        throw new RuntimeException($e->getMessage());
                    }
                };

                // Combina middlewares globais e da rota
                $middlewares = array_merge($this->globalMiddlewares, $routeMiddlewares);

                // Cria a cadeia de middlewares + controller
                $middlewareChain = array_reduce(
                    array_reverse($middlewares),
                    function ($next, $middlewareClass) {
                        return function (Request $request) use ($middlewareClass, $next) {
                            /** @var MiddlewareInterface $middleware */
                            $middleware = $this->container->get($middlewareClass);
                            return $middleware->handle($request, $next);
                        };
                    },
                    $controllerCallable
                );

                // Executa a cadeia
                return $middlewareChain($request);
            }
        }

        return new Response('Page not found', 404);
    }

    /**
     * Retorna a URL da rota pelo nome/alias.
     */
    public function url(string $name): ?string
    {
        return $this->namedRoutes[$name] ?? null;
    }
}