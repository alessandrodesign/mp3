<?php

namespace App\Services\Auth;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTService
{
    private string $secretKey = SECRET_KEY_GLOBAL;
    private string $algorithm = 'HS256';

    public function generateToken(UserInterface $user, array $payload = []): string
    {
        $now = time();
        $future = strtotime('+1 hour', $now);

        $data = [
            'iat' => $now,
            'exp' => $future,
            'sub' => $user->getUsername(), // ou ID do usuário
            'data' => $payload,
        ];

        return JWT::encode($data, $this->secretKey, $this->algorithm);
    }

    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secretKey, $this->algorithm));
        } catch (Exception $e) {
            // Token inválido ou expirado
            return null;
        }
    }
}