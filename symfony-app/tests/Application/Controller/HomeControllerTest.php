<?php

declare(strict_types=1);

namespace App\Tests\Application\Controller;

use App\Tests\BaseWebTestCase;
use App\Tests\Fixtures\EntityFixtures;
use App\Service\PhotoFilterService;

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

    public function testFilterFormIsDisplayed(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $this->assertSelectorExists('form[method="GET"]');
        $this->assertSelectorExists('input[name="location"]');
        $this->assertSelectorExists('input[name="camera"]');
        $this->assertSelectorExists('input[name="description"]');
        $this->assertSelectorExists('input[name="username"]');
        $this->assertSelectorExists('input[name="taken_at_from"]');
        $this->assertSelectorExists('input[name="taken_at_to"]');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testFilterByLocation(): void
    {
        $user = $this->createTestUser('testuser');
        $photo1 = EntityFixtures::createPhoto($this->getEntityManager(), $user);
        $photo2 = EntityFixtures::createPhoto($this->getEntityManager(), $user);
        
        $photo1->setLocation('Paris');
        $photo2->setLocation('London');
        $this->getEntityManager()->flush();

        $crawler = $this->client->request('GET', '/', ['location' => 'Paris']);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Paris', $crawler->text());
        $this->assertStringNotContainsString('London', $crawler->text());
    }

    public function testFilterByCamera(): void
    {
        $user = $this->createTestUser('testuser');
        $photo1 = EntityFixtures::createPhoto($this->getEntityManager(), $user);
        $photo2 = EntityFixtures::createPhoto($this->getEntityManager(), $user);
        
        $photo1->setCamera('Canon EOS');
        $photo2->setCamera('Nikon D850');
        $this->getEntityManager()->flush();

        $crawler = $this->client->request('GET', '/', ['camera' => 'Canon']);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Canon', $crawler->text());
        $this->assertStringNotContainsString('Nikon', $crawler->text());
    }

    public function testFilterByUsername(): void
    {
        $user1 = $this->createTestUser('photographer1');
        $user2 = $this->createTestUser('photographer2');
        $photo1 = EntityFixtures::createPhoto($this->getEntityManager(), $user1);
        $photo2 = EntityFixtures::createPhoto($this->getEntityManager(), $user2);
        $this->getEntityManager()->flush();

        $crawler = $this->client->request('GET', '/', ['username' => 'photographer1']);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('photographer1', $crawler->text());
        $this->assertStringNotContainsString('photographer2', $crawler->text());
    }

    public function testFilterByDescription(): void
    {
        $user = $this->createTestUser('testuser');
        $photo1 = EntityFixtures::createPhoto($this->getEntityManager(), $user);
        $photo2 = EntityFixtures::createPhoto($this->getEntityManager(), $user);
        
        $photo1->setDescription('Beautiful sunset over mountains');
        $photo2->setDescription('City lights at night');
        $this->getEntityManager()->flush();

        $crawler = $this->client->request('GET', '/', ['description' => 'sunset']);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('sunset', $crawler->text());
        $this->assertStringNotContainsString('City lights', $crawler->text());
    }

    public function testFilterByDateRange(): void
    {
        $user = $this->createTestUser('testuser');
        $photo1 = EntityFixtures::createPhoto($this->getEntityManager(), $user);
        $photo2 = EntityFixtures::createPhoto($this->getEntityManager(), $user);
        
        $photo1->setTakenAt(new \DateTimeImmutable('2023-01-15'));
        $photo2->setTakenAt(new \DateTimeImmutable('2023-02-20'));
        $this->getEntityManager()->flush();

        $crawler = $this->client->request('GET', '/', [
            'taken_at_from' => '2023-01-01',
            'taken_at_to' => '2023-01-31'
        ]);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Test Description', $crawler->text());
    }

    public function testMultipleFilters(): void
    {
        $user1 = $this->createTestUser('photographer1');
        $user2 = $this->createTestUser('photographer2');
        $photo1 = EntityFixtures::createPhoto($this->getEntityManager(), $user1);
        $photo2 = EntityFixtures::createPhoto($this->getEntityManager(), $user2);
        
        $photo1->setLocation('Paris');
        $photo1->setCamera('Canon EOS');
        $photo2->setLocation('London');
        $photo2->setCamera('Nikon D850');
        $this->getEntityManager()->flush();

        $crawler = $this->client->request('GET', '/', [
            'location' => 'Paris',
            'camera' => 'Canon'
        ]);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Paris', $crawler->text());
        $this->assertStringContainsString('Canon', $crawler->text());
        $this->assertStringNotContainsString('London', $crawler->text());
        $this->assertStringNotContainsString('Nikon', $crawler->text());
    }

    public function testFilterWithNoResults(): void
    {
        $user = $this->createTestUser('testuser');
        $photo = EntityFixtures::createPhoto($this->getEntityManager(), $user);
        $photo->setLocation('Paris');
        $this->getEntityManager()->flush();

        $crawler = $this->client->request('GET', '/', ['location' => 'NonExistentCity']);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('No photos found', $crawler->text());
        $this->assertStringContainsString('clear all filters', $crawler->text());
    }

    public function testFilterFormValuesPersist(): void
    {
        $crawler = $this->client->request('GET', '/', [
            'location' => 'Paris',
            'camera' => 'Canon'
        ]);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $this->assertSelectorExists('input[name="location"][value="Paris"]');
        $this->assertSelectorExists('input[name="camera"][value="Canon"]');
    }

    public function testClearFiltersLink(): void
    {
        $crawler = $this->client->request('GET', '/', ['location' => 'Paris']);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('a[href="/"]:contains("Clear All")');
    }
}
