<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Entity\AuthToken;
use App\Entity\Photo;
use App\Entity\User;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class EntityFixtures
{
    public static function createPhoto(
        EntityManagerInterface $entityManager,
        User $user,
        array $data = []
    ): Photo {
        $photo = new Photo();
        $photo->setImageUrl($data['imageUrl'] ?? 'https://example.com/photo.jpg');
        $photo->setLocation($data['location'] ?? 'Test Location');
        $photo->setDescription($data['description'] ?? 'Test Description');
        $photo->setCamera($data['camera'] ?? 'Test Camera');
        $photo->setTakenAt($data['takenAt'] ?? new DateTimeImmutable('2026-02-02 08:00:00'));
        $photo->setLikeCounter($data['likeCounter'] ?? 0);
        $photo->setUser($user);

        $entityManager->persist($photo);
        $entityManager->flush();

        return $photo;
    }

    public static function createAuthToken(
        EntityManagerInterface $entityManager,
        User $user,
        string $token = 'valid-token-123'
    ): AuthToken {
        $authToken = new AuthToken();
        $authToken->setToken($token);
        $authToken->setUser($user);
        $authToken->setCreatedAt(new DateTime());

        $entityManager->persist($authToken);
        $entityManager->flush();

        return $authToken;
    }
}
