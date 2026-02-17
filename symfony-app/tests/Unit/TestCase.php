<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
    }
    
    protected function createMockUser(array $data = []): \App\Entity\User
    {
        $user = new \App\Entity\User();
        
        if (isset($data['id'])) {
            $reflection = new \ReflectionClass($user);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setValue($user, $data['id']);
        }
        
        $user->setUsername($data['username'] ?? 'testuser');
        $user->setEmail($data['email'] ?? 'test@example.com');
        $user->setName($data['name'] ?? 'Test');
        $user->setLastName($data['lastName'] ?? 'User');
        $user->setAge($data['age'] ?? 25);
        $user->setBio($data['bio'] ?? 'Test bio');
        
        return $user;
    }
    
    protected function createMockPhoto(array $data = []): \App\Entity\Photo
    {
        $photo = new \App\Entity\Photo();
        
        if (isset($data['id'])) {
            $reflection = new \ReflectionClass($photo);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setValue($photo, $data['id']);
        }
        
        $user = $data['user'] ?? $this->createMockUser();
        
        $photo->setImageUrl($data['imageUrl'] ?? 'https://example.com/photo.jpg');
        $photo->setLocation($data['location'] ?? 'Test Location');
        $photo->setDescription($data['description'] ?? 'Test Description');
        $photo->setCamera($data['camera'] ?? 'Test Camera');
        $photo->setTakenAt($data['takenAt'] ?? new \DateTimeImmutable());
        $photo->setLikeCounter($data['likeCounter'] ?? 0);
        $photo->setUser($user);
        
        return $photo;
    }
    
    protected function createMockAuthToken(array $data = []): \App\Entity\AuthToken
    {
        $token = new \App\Entity\AuthToken();
        
        if (isset($data['id'])) {
            $reflection = new \ReflectionClass($token);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setValue($token, $data['id']);
        }
        
        $user = $data['user'] ?? $this->createMockUser();
        
        $token->setToken($data['token'] ?? 'test-token-123');
        $token->setUser($user);
        $token->setCreatedAt($data['createdAt'] ?? new \DateTime());
        
        return $token;
    }
}
