<?php

namespace Middleware;

use Core\Contracts\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Exemplo simples: verifica se existe um header "X-Auth"
        if (!$request->headers->has('X-Auth')) {
            return new Response('Não autorizado', 401);
        }

        return $next($request);
    }
}