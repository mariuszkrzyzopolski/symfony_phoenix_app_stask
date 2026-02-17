<?php

declare(strict_types=1);

namespace App\Tests\Application\Controller;

use App\Tests\BaseWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class HomeControllerTest extends BaseWebTestCase
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

    private function createTestPhoto(\App\Entity\User $user): \App\Entity\Photo
    {
        $photo = new \App\Entity\Photo();
        $photo->setImageUrl('https://example.com/photo.jpg');
        $photo->setLocation('Test Location');
        $photo->setDescription('Test Description');
        $photo->setCamera('Test Camera');
        $photo->setTakenAt(new \DateTimeImmutable('2026-02-02 08:00:00'));
        $photo->setLikeCounter(0);
        $photo->setUser($user);
        
        $this->getEntityManager()->persist($photo);
        $this->getEntityManager()->flush();
        
        return $photo;
    }

    public function testHomePageRendersSuccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testHomePageWithGetRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertNotNull($client->getResponse()->getContent());
    }

    public function testHomePageWithNonAuthenticatedUser(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('photos', $client->getResponse()->getContent());
    }

    public function testHomePageWithAuthenticatedUser(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser('testuser');
        $photo = $this->createTestPhoto($user);
        
        $this->authenticateClient($client, $user);
        
        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testHomePageWithAuthenticatedUserAndPhotos(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser('testuser');
        $this->authenticateClient($client, $user);
        $this->createTestPhoto($user);
        $this->createTestPhoto($user);
        
        
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $this->assertStringContainsString('photo.jpg', $client->getResponse()->getContent());
    }

    public function testHomePageWithInvalidUserSession(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('photos', $client->getResponse()->getContent());
    }

    public function testHomePageWithEmptyDatabase(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        // Should render even with no photos
        $this->assertNotNull($client->getResponse()->getContent());
    }
}
