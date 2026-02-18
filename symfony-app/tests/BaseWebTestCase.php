<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

abstract class BaseWebTestCase extends WebTestCase
{
    protected ?KernelBrowser $client = null;
    protected ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        static::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        if ($this->entityManager !== null && $this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        if ($this->entityManager !== null) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
        
        $this->client = null;
        
        // Ensure kernel is shut down to prevent interference between test classes
        static::ensureKernelShutdown();
    }

    public static function tearDownAfterClass(): void
    {
        static::ensureKernelShutdown();
        parent::tearDownAfterClass();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        if ($this->entityManager === null) {
            $this->entityManager = static::getContainer()->get('doctrine')->getManager();
            $this->entityManager->beginTransaction();
        }
        return $this->entityManager;
    }

    protected function authenticateClient(?KernelBrowser $client = null, ?User $user = null): void
    {
        $client = $client ?? $this->client;
        if ($client === null) {
            throw new \RuntimeException('No client available for authentication');
        }
        if ($user === null) {
            throw new \RuntimeException('No user provided for authentication');
        }

        $container = $client->getContainer();
        $session = $container->get('session.factory')->createSession();
        $session->set('user_id', $user->getId());
        $session->set('username', $user->getUsername());
        $session->save();

        $cookie = new Cookie(
            $session->getName(),
            $session->getId(),
            null,
            '/',
            '',
            false,
            false,
            false,
            'Lax'
        );
        $client->getCookieJar()->set($cookie);
    }

    protected function createTestUser(string $username = null, array $additionalData = []): User
    {
        $username = $username ?? 'testuser_' . uniqid();

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username . '@example.com');
        $user->setName($additionalData['name'] ?? 'Test');
        $user->setLastName($additionalData['lastName'] ?? 'User');
        if (isset($additionalData['age'])) {
            $user->setAge($additionalData['age']);
        }
        if (isset($additionalData['bio'])) {
            $user->setBio($additionalData['bio']);
        }
        if (isset($additionalData['phoenixAccessToken'])) {
            $user->setPhoenixAccessToken($additionalData['phoenixAccessToken']);
        }

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }
}
