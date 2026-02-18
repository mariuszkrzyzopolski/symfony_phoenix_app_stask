<?php

declare(strict_types=1);

namespace App\Tests\Application\Controller;

use App\Tests\BaseWebTestCase;
use App\Tests\Fixtures\EntityFixtures;

class AuthControllerTest extends BaseWebTestCase
{

    public function testLoginPageWithInvalidToken(): void
    {
        $crawler = $this->client->request('GET', '/auth/testuser/invalid-token');

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Invalid token', $crawler->text());
    }

    public function testLoginPageWithValidTokenAndExistingUser(): void
    {
        $user = $this->createTestUser('testuser');
        EntityFixtures::createAuthToken($this->getEntityManager(), $user, 'valid-token-123');
        
        $this->client->request('GET', '/auth/testuser/valid-token-123');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $this->client->getResponse()->headers->get('location'));
        
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Welcome back, testuser!', $crawler->text());
    }

    public function testLoginPageWithValidTokenButNonExistentUser(): void
    {
        $user = $this->createTestUser('testuser');
        EntityFixtures::createAuthToken($this->getEntityManager(), $user, 'valid-token-123');
        
        $crawler = $this->client->request('GET', '/auth/nonexistentuser/valid-token-123');

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('User not found', $crawler->text());
    }

    public function testLogoutPageClearsSessionAndRedirects(): void
    {
        $user = $this->createTestUser('testuser');
        
        $this->authenticateClient($this->client, $user);
        
        $this->client->request('GET', '/logout');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $this->client->getResponse()->headers->get('location'));
        
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('You have been logged out successfully', $crawler->text());
    }

    public function testLogoutPageWhenNotLoggedIn(): void
    {
        $this->client->request('GET', '/logout');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $this->client->getResponse()->headers->get('location'));
        
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('You have been logged out successfully', $crawler->text());
    }
}
