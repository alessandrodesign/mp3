<?php

namespace App\Controllers;

use Core\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebController
{
    #[Route('/', 'GET', 'web.index')]
    public function index(Request $request): Response
    {
        return new Response();
    }
}