<?php

namespace App\Services\Auth;

use App\Security\User;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserService implements UserProviderInterface
{
    // Simula um banco de dados de usuários
    private array $users = [
        'john' => ['password' => '123', 'roles' => ['ROLE_USER']],
        'admin' => ['password' => 'admin', 'roles' => ['ROLE_ADMIN']],
    ];

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (!isset($this->users[$identifier])) {
            throw new UsernameNotFoundException("User not found: $identifier");
        }

        $userData = $this->users[$identifier];
        return new User($identifier, $userData['password'], $userData['roles']);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUsername());
    }

    public function supportsClass($class): bool
    {
        return $class === User::class;
    }

    function loadUserByUsername($username)
    {
        // TODO: Implement loadUserByUsername() method.
    }
}