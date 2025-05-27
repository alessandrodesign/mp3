<?php

namespace App\Middlewares;

use App\Services\Auth\JWTService;
use App\Services\Auth\UserService;
use Core\Contracts\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthMiddleware implements MiddlewareInterface
{
    private JWTService $jwtService;
    private UserService $userProvider;

    public function __construct(JWTService $jwtService, UserService $userProvider)
    {
        $this->jwtService = $jwtService;
        $this->userProvider = $userProvider;
    }

    public function handle(Request $request, callable $next): Response
    {
        $token = $request->headers->get('Authorization');

        if (!$token) {
            return new JsonResponse(['message' => 'Authentication required'], 401);
        }

        $token = str_replace('Bearer ', '', $token);
        $payload = $this->jwtService->validateToken($token);

        if (!$payload) {
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }

        try {
            $user = $this->userProvider->loadUserByIdentifier($payload->sub);
            $request->attributes->set('user', $user);
            return $next($request);
        } catch (AuthenticationException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 401);
        }
    }
}