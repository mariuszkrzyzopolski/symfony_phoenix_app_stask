<?php

declare(strict_types=1);

namespace App\Tests\Application\Controller;

use App\Tests\BaseWebTestCase;

class ProfileControllerTest extends BaseWebTestCase
{

    public function testProfilePageRedirectsWhenNotLoggedIn(): void
    {
        $this->client->request('GET', '/profile');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $this->client->getResponse()->headers->get('location'));
    }

    public function testProfilePageWithAuthenticatedUser(): void
    {
        $user = $this->createTestUser('testuser', ['age' => 25, 'bio' => 'Test bio']);
        
        $this->authenticateClient($this->client, $user);
        
        $crawler = $this->client->request('GET', '/profile');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $this->assertStringContainsString('testuser', $crawler->text());
        $this->assertStringContainsString('Test', $crawler->text());
        $this->assertStringContainsString('User', $crawler->text());
    }

    public function testProfilePageWithInvalidUserSession(): void
    {
        $this->client->request('GET', '/profile');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $this->client->getResponse()->headers->get('location'));
    }

    public function testProfilePageWithValidUserSession(): void
    {
        $user = $this->createTestUser('testuser', ['age' => 25, 'bio' => 'Test bio']);
        
        $this->authenticateClient($this->client, $user);
        
        $this->client->request('GET', '/profile');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testProfilePageDisplaysUserDetails(): void
    {
        $user = $this->createTestUser('john', ['age' => 25, 'bio' => 'Test bio']);
        
        $this->authenticateClient($this->client, $user);
        
        $crawler = $this->client->request('GET', '/profile');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $this->assertStringContainsString('john', $crawler->text());
        $this->assertStringContainsString('Test bio', $crawler->text());
    }
}
