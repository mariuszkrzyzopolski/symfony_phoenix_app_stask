<?php

declare(strict_types=1);

namespace App\Tests\Application\Controller;

use App\Tests\BaseWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class AuthControllerTest extends BaseWebTestCase
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

    private function createTestAuthToken(\App\Entity\User $user, string $token = 'valid-token-123'): \App\Entity\AuthToken
    {
        $authToken = new \App\Entity\AuthToken();
        $authToken->setToken($token);
        $authToken->setUser($user);
        $authToken->setCreatedAt(new \DateTime());
        
        $this->getEntityManager()->persist($authToken);
        $this->getEntityManager()->flush();
        
        return $authToken;
    }

    public function testLoginPageWithInvalidToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/auth/testuser/invalid-token');

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Invalid token', $client->getResponse()->getContent());
    }

    public function testLoginPageWithValidTokenAndExistingUser(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser('testuser');
        $this->createTestAuthToken($user, 'valid-token-123');
        
        $client->request('GET', '/auth/testuser/valid-token-123');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $client->getResponse()->headers->get('location'));
        
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Welcome back, testuser!', $crawler->text());
    }

    public function testLoginPageWithValidTokenButNonExistentUser(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser('testuser');
        $this->createTestAuthToken($user, 'valid-token-123');
        
        $client->request('GET', '/auth/nonexistentuser/valid-token-123');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('User not found', $client->getResponse()->getContent());
    }

    public function testLogoutPageClearsSessionAndRedirects(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUser('testuser');
        
        $this->authenticateClient($client, $user);
        
        $client->request('GET', '/logout');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $client->getResponse()->headers->get('location'));
        
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('You have been logged out successfully', $crawler->text());
    }

    public function testLogoutPageWhenNotLoggedIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/logout');

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $client->getResponse()->headers->get('location'));
        
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('You have been logged out successfully', $crawler->text());
    }
}
