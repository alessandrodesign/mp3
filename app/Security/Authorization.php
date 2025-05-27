<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class Authorization
{
    public static function isGranted(UserInterface $user, string $role): bool
    {
        return in_array($role, $user->getRoles(), true);
    }
}