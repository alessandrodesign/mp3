<?php

namespace Core\Routing;

use Attribute;
use InvalidArgumentException;

/**
 * Define uma rota HTTP associada a um método de controller.
 *
 * Exemplo de uso:
 * #[Route('/home', 'GET', 'home.index')]
 *
 * @package Core\Routing
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public string $path;
    public array $methods;
    public ?string $name;
    private const array ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

    /**
     * Construtor da rota.
     *
     * @param string $path Caminho da URL (ex: "/home")
     * @param string|array $method Método(s) HTTP permitidos (ex: "GET" ou ["GET", "POST"])
     * @param string|null $name Nome da rota (ex: "home.index")
     */
    public function __construct(string $path, string|array $method = 'GET', ?string $name = null)
    {
        $methods = is_array($method) ? $method : [$method];
        $this->methods = array_map('strtoupper', $methods);

        foreach ($this->methods as $m) {
            if (!in_array($m, self::ALLOWED_METHODS, true)) {
                throw new InvalidArgumentException("Invalid HTTP method: {$m}");
            }
        }

        $this->path = $path;
        $this->name = $name;
    }
}
