<?php

declare(strict_types=1);

namespace App\Tests\Application\Controller;

use App\Tests\BaseWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class PhotoControllerTest extends BaseWebTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    private function getEntityManager(): EntityManagerInterface
    {
        if ($this->entityManager === null) {
            $this->entityManager = static::getContainer()->get('doctrine')->getManager();
            $this->entityManager->beginTransaction();
        }
        return $this->entityManager;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->entityManager !== null && $this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        if ($this->entityManager !== null) {
            $this->entityManager->close();
        }
    }

    private function createTestUser(string $username = 'testuser'): \App\Entity\User
    {
        $user = new \App\Entity\User();
        $user->setUsername($username);
        $user->setEmail($username . '@example.com');
        $user->setName('Test');
        $user->setLastName('User');
        
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        
        return $user;
    }

    // TODO: rewrite it and simplify to not use direct query
    private function createTestPhoto(\App\Entity\User $user): \App\Entity\Photo
    {
        $connection = $this->getEntityManager()->getConnection();
        
        $imageUrl = 'https://example.com/photo.jpg';
        $location = 'Test Location';
        $description = 'Test Description';
        $camera = 'Test Camera';
        $userId = $user->getId();
        
        $sql = "INSERT INTO photos (id, user_id, image_url, location, description, camera, like_counter) 
                VALUES (nextval('photos_id_seq'), ?, ?, ?, ?, ?, 0) 
                RETURNING id";
        $photoId = $connection->executeQuery($sql, [$userId, $imageUrl, $location, $description, $camera])->fetchOne();
        $this->getEntityManager()->clear();
        
        $photo = $this->getEntityManager()->getRepository(\App\Entity\Photo::class)->find($photoId);
        
        if (!$photo) {
            throw new \RuntimeException("Failed to create test photo with ID: $photoId");
        }
        
        return $photo;
    }

    public function testLikePhotoRedirectsWhenNotLoggedIn(): void
    {
        $client = static::createClient();
        $client->request('POST', '/photo/1/like');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $client->getResponse()->headers->get('location'));
        
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('You must be logged in to like photos', $crawler->text());
    }

    public function testLikePhotoWithValidUserAndPhoto(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser();
        $photo = $this->createTestPhoto($user);
        
        $this->authenticateClient($client, $user);
        
        $client->request('POST', '/photo/' . $photo->getId() . '/like');
        
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertEquals('/', $client->getResponse()->headers->get('location'));
        
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Photo liked!', $crawler->text());
    }

    public function testLikePhotoWithNonExistentPhoto(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser();
        
        $client->request('POST', '/photo/999/like');

        $this->assertTrue($client->getResponse()->isRedirect());
    }

    // TODO: rewrite it and simplify to not use direct query
    public function testUnlikePhotoWithValidUserAndPhoto(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser();
        $photo = $this->createTestPhoto($user);
        
        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement(
            "INSERT INTO likes (id, user_id, photo_id, created_at) VALUES (nextval('likes_id_seq'), ?, ?, ?)",
            [$user->getId(), $photo->getId(), (new \DateTime())->format('Y-m-d H:i:s')]
        );
        
        $this->authenticateClient($client, $user);
        
        $client->request('POST', '/photo/' . $photo->getId() . '/like');
        
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertEquals('/', $client->getResponse()->headers->get('location'));
        
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Photo unliked!', $crawler->text());
    }

    public function testLikePhotoWithInvalidUserSession(): void
    {
        $client = static::createClient();
        $client->request('POST', '/photo/1/like');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $client->getResponse()->headers->get('location'));
        
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('You must be logged in to like photos', $crawler->text());
    }
}
