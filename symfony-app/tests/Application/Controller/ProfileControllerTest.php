<?php

declare(strict_types=1);

namespace App\Tests\Application\Controller;

use App\Tests\BaseWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ProfileControllerTest extends BaseWebTestCase
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
        // Generate unique username to avoid conflicts
        $uniqueUsername = $username . '_' . uniqid();
        
        $user = new \App\Entity\User();
        $user->setUsername($uniqueUsername);
        $user->setEmail($uniqueUsername . '@example.com');
        $user->setName('Test');
        $user->setLastName('User');
        $user->setAge(25);
        $user->setBio('Test bio');
        
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        
        return $user;
    }

    public function testProfilePageRedirectsWhenNotLoggedIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $client->getResponse()->headers->get('location'));
    }

    public function testProfilePageWithAuthenticatedUser(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser('testuser');
        
        $this->authenticateClient($client, $user);
        
        $crawler = $client->request('GET', '/profile');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $this->assertStringContainsString('testuser', $crawler->text());
        $this->assertStringContainsString('Test', $crawler->text());
        $this->assertStringContainsString('User', $crawler->text());
    }

    public function testProfilePageWithInvalidUserSession(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $client->getResponse()->headers->get('location'));
    }

    public function testProfilePageWithValidUserSession(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser('testuser');
        
        $this->authenticateClient($client, $user);
        
        $client->request('GET', '/profile');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testProfilePageDisplaysUserDetails(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser('john');
        
        $this->authenticateClient($client, $user);
        
        $crawler = $client->request('GET', '/profile');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $this->assertStringContainsString('john', $crawler->text());
        $this->assertStringContainsString('Test bio', $crawler->text());
    }
}
