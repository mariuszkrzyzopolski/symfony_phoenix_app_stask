<?php

declare(strict_types=1);

namespace App\Tests\Application\Controller;

use App\Tests\BaseWebTestCase;

class PhotoControllerTest extends BaseWebTestCase
{
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
        $this->client->request('POST', '/photo/1/like');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $this->client->getResponse()->headers->get('location'));
        
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('You must be logged in to like photos', $crawler->text());
    }

    public function testLikePhotoWithValidUserAndPhoto(): void
    {
        $user = $this->createTestUser();
        $photo = $this->createTestPhoto($user);
        
        $this->authenticateClient($this->client, $user);
        
        $this->client->request('POST', '/photo/' . $photo->getId() . '/like');
        
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertEquals('/', $this->client->getResponse()->headers->get('location'));
        
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Photo liked!', $crawler->text());
    }

    public function testLikePhotoWithNonExistentPhoto(): void
    {
        $user = $this->createTestUser();
        
        $this->client->request('POST', '/photo/999/like');

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    // TODO: rewrite it and simplify to not use direct query
    public function testUnlikePhotoWithValidUserAndPhoto(): void
    {
        $user = $this->createTestUser();
        $photo = $this->createTestPhoto($user);
        
        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement(
            "INSERT INTO likes (id, user_id, photo_id, created_at) VALUES (nextval('likes_id_seq'), ?, ?, ?)",
            [$user->getId(), $photo->getId(), (new \DateTime())->format('Y-m-d H:i:s')]
        );
        
        $this->authenticateClient($this->client, $user);
        
        $this->client->request('POST', '/photo/' . $photo->getId() . '/like');
        
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertEquals('/', $this->client->getResponse()->headers->get('location'));
        
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('Photo unliked!', $crawler->text());
    }

    public function testLikePhotoWithInvalidUserSession(): void
    {
        $this->client->request('POST', '/photo/1/like');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/', $this->client->getResponse()->headers->get('location'));
        
        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('You must be logged in to like photos', $crawler->text());
    }
}
