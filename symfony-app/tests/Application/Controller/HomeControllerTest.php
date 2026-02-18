<?php

declare(strict_types=1);

namespace App\Tests\Application\Controller;

use App\Tests\BaseWebTestCase;
use App\Tests\Fixtures\EntityFixtures;

class HomeControllerTest extends BaseWebTestCase
{

    public function testHomePageRendersSuccess(): void
    {
        $this->client->request('GET', '/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testHomePageWithGetRequest(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('html');
        $this->assertNotEmpty($crawler->text());
    }

    public function testHomePageWithNonAuthenticatedUser(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('html');
        $this->assertStringContainsString('photos', $crawler->text());
    }

    public function testHomePageWithAuthenticatedUser(): void
    {
        $user = $this->createTestUser('testuser');
        $photo = EntityFixtures::createPhoto($this->getEntityManager(), $user);
        
        $this->authenticateClient($this->client, $user);
        
        $crawler = $this->client->request('GET', '/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testHomePageWithAuthenticatedUserAndPhotos(): void
    {
        $user = $this->createTestUser('testuser');
        $this->authenticateClient($this->client, $user);
        EntityFixtures::createPhoto($this->getEntityManager(), $user);
        EntityFixtures::createPhoto($this->getEntityManager(), $user);
        
        
        $crawler = $this->client->request('GET', '/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $this->assertStringContainsString('Test Description', $crawler->text());
    }

    public function testHomePageWithInvalidUserSession(): void
    {
        $crawler = $this->client->request('GET', '/');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('html');
        $this->assertStringContainsString('photos', $crawler->text());
    }

    public function testHomePageWithEmptyDatabase(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        // Should render even with no photos
        $this->assertSelectorExists('html');
        $this->assertNotEmpty($crawler->text());
    }
}
