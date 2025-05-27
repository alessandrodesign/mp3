<?php

namespace Core\Contracts;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareInterface
{
    /**
     * Executa o middleware.
     *
     * @param Request $request
     * @param callable $next Função para chamar o próximo middleware/controller
     * @return Response
     */
    public function handle(Request $request, callable $next): Response;
}