<?php

namespace App\Controllers;

use Core\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiController
{
    #[Route('/api/data', ['GET', 'POST'], 'api.data')]
    public function data(Request $request): Response
    {
        return new Response('Dados da API');
    }
}