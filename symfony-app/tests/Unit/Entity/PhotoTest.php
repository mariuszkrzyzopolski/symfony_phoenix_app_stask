<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Photo;
use App\Entity\User;
use App\Tests\Unit\TestCase;

class PhotoTest extends TestCase
{
    private Photo $photo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->photo = new Photo();
    }

    public function testGetIdReturnsNullInitially(): void
    {
        $this->assertNull($this->photo->getId());
    }

    public function testImageUrlSettersAndGetters(): void
    {
        $imageUrl = 'https://example.com/photo.jpg';
        
        $this->photo->setImageUrl($imageUrl);
        $this->assertEquals($imageUrl, $this->photo->getImageUrl());
    }

    public function testLocationSettersAndGetters(): void
    {
        $location = 'Paris, France';
        
        $this->photo->setLocation($location);
        $this->assertEquals($location, $this->photo->getLocation());
    }

    public function testLocationCanBeNull(): void
    {
        $this->photo->setLocation(null);
        $this->assertNull($this->photo->getLocation());
    }

    public function testDescriptionSettersAndGetters(): void
    {
        $description = 'A beautiful sunset over the mountains';
        
        $this->photo->setDescription($description);
        $this->assertEquals($description, $this->photo->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $this->photo->setDescription(null);
        $this->assertNull($this->photo->getDescription());
    }

    public function testCameraSettersAndGetters(): void
    {
        $camera = 'Canon';
        
        $this->photo->setCamera($camera);
        $this->assertEquals($camera, $this->photo->getCamera());
    }

    public function testCameraCanBeNull(): void
    {
        $this->photo->setCamera(null);
        $this->assertNull($this->photo->getCamera());
    }

    public function testTakenAtSettersAndGetters(): void
    {
        $takenAt = new \DateTimeImmutable('2026-02-02 08:00:00');
        
        $this->photo->setTakenAt($takenAt);
        $this->assertEquals($takenAt, $this->photo->getTakenAt());
    }

    public function testTakenAtCanBeNull(): void
    {
        $this->photo->setTakenAt(null);
        $this->assertNull($this->photo->getTakenAt());
    }

    public function testLikeCounterSettersAndGetters(): void
    {
        $likeCounter = 50;
        
        $this->photo->setLikeCounter($likeCounter);
        $this->assertEquals($likeCounter, $this->photo->getLikeCounter());
    }

    public function testLikeCounterDefaultsToZero(): void
    {
        $this->assertEquals(0, $this->photo->getLikeCounter());
    }

    public function testUserSettersAndGetters(): void
    {
        $user = $this->createMockUser();
        
        $this->photo->setUser($user);
        $this->assertEquals($user, $this->photo->getUser());
    }

    public function testUserCannotBeNull(): void
    {
        $user = $this->createMockUser();
        $this->photo->setUser($user);
        
        $this->assertEquals($user, $this->photo->getUser());
        $this->assertInstanceOf(User::class, $this->photo->getUser());
    }

    public function testPhotoCreationWithAllProperties(): void
    {
        $user = $this->createMockUser();
        $takenAt = new \DateTimeImmutable('2023-12-25 10:00:00');
        
        $this->photo->setImageUrl('https://example.com/beach.jpg');
        $this->photo->setLocation('Miami Beach');
        $this->photo->setDescription('Beautiful sunset at the beach');
        $this->photo->setCamera('Sony A7III');
        $this->photo->setTakenAt($takenAt);
        $this->photo->setLikeCounter(15);
        $this->photo->setUser($user);

        $this->assertEquals('https://example.com/beach.jpg', $this->photo->getImageUrl());
        $this->assertEquals('Miami Beach', $this->photo->getLocation());
        $this->assertEquals('Beautiful sunset at the beach', $this->photo->getDescription());
        $this->assertEquals('Sony A7III', $this->photo->getCamera());
        $this->assertEquals($takenAt, $this->photo->getTakenAt());
        $this->assertEquals(15, $this->photo->getLikeCounter());
        $this->assertEquals($user, $this->photo->getUser());
    }

    public function testPhotoWithMinimalData(): void
    {
        $user = $this->createMockUser();
        
        $this->photo->setImageUrl('https://example.com/minimal.jpg');
        $this->photo->setUser($user);

        $this->assertEquals('https://example.com/minimal.jpg', $this->photo->getImageUrl());
        $this->assertEquals($user, $this->photo->getUser());
        $this->assertNull($this->photo->getLocation());
        $this->assertNull($this->photo->getDescription());
        $this->assertNull($this->photo->getCamera());
        $this->assertNull($this->photo->getTakenAt());
        $this->assertEquals(0, $this->photo->getLikeCounter());
    }

    public function testLikeCounterCanBeUpdated(): void
    {
        $this->photo->setLikeCounter(5);
        $this->assertEquals(5, $this->photo->getLikeCounter());

        $this->photo->setLikeCounter(10);
        $this->assertEquals(10, $this->photo->getLikeCounter());
    }
}
