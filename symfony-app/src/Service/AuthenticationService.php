<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuthToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AuthenticationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function validateToken(string $token): ?AuthToken
    {
        return $this->entityManager->getRepository(AuthToken::class)->findOneBy(['token' => $token]);
    }

    public function findUserByUsername(string $username): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    public function authenticateUser(string $username, string $token): ?User
    {
        $tokenData = $this->validateToken($token);
        
        if (!$tokenData) {
            return null;
        }

        $userData = $this->findUserByUsername($username);
        
        if (!$userData) {
            return null;
        }

        return $userData;
    }
}
