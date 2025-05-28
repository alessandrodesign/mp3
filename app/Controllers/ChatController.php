<?php

namespace App\Controllers;

use Core\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    #[Route('/chat/room/{id}', 'GET', 'chat.room')]
    public function roomPublic(Request $request, string $id = null): Response
    {
        return $this->view('chat.room', compact('request', 'id'));
    }

    #[Route('/chat/private/{from}/{to}', 'GET', 'chat.private')]
    public function roomPrivate(Request $request, string $from = null, string $to = null): Response
    {
        return $this->view('chat.private', compact('request', 'from', 'to'));
    }
}