<?php

namespace App\Controllers;

use Core\Routing\Route;
use Core\Routing\Middleware;
use Middleware\AuthMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController
{
    #[Route('/user/profile', 'GET', 'user.profile')]
    #[Middleware(AuthMiddleware::class)]
    public function profile(Request $request): Response
    {
        // Aqui você pode usar injeção de dependências no método, ex: Request, serviços etc.
        return new Response('Perfil do usuário');
    }
}