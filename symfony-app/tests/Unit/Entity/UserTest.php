<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Tests\Unit\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
    }

    public function testGetIdReturnsNullInitially(): void
    {
        $this->assertNull($this->user->getId());
    }

    public function testUsernameSettersAndGetters(): void
    {
        $username = 'testuser';
        
        $this->user->setUsername($username);
        $this->assertEquals($username, $this->user->getUsername());
    }

    public function testEmailSettersAndGetters(): void
    {
        $email = 'test@example.com';
        
        $this->user->setEmail($email);
        $this->assertEquals($email, $this->user->getEmail());
    }

    public function testNameSettersAndGetters(): void
    {
        $name = 'John';
        
        $this->user->setName($name);
        $this->assertEquals($name, $this->user->getName());
    }

    public function testNameCanBeNull(): void
    {
        $this->user->setName(null);
        $this->assertNull($this->user->getName());
    }

    public function testLastNameSettersAndGetters(): void
    {
        $lastName = 'Doe';
        
        $this->user->setLastName($lastName);
        $this->assertEquals($lastName, $this->user->getLastName());
    }

    public function testLastNameCanBeNull(): void
    {
        $this->user->setLastName(null);
        $this->assertNull($this->user->getLastName());
    }

    public function testAgeSettersAndGetters(): void
    {
        $age = 30;
        
        $this->user->setAge($age);
        $this->assertEquals($age, $this->user->getAge());
    }

    public function testAgeCanBeNull(): void
    {
        $this->user->setAge(null);
        $this->assertNull($this->user->getAge());
    }

    public function testBioSettersAndGetters(): void
    {
        $bio = 'Test biography';
        
        $this->user->setBio($bio);
        $this->assertEquals($bio, $this->user->getBio());
    }

    public function testBioCanBeNull(): void
    {
        $this->user->setBio(null);
        $this->assertNull($this->user->getBio());
    }

    public function testPhotosCollectionInitialization(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->user->getPhotos());
        $this->assertCount(0, $this->user->getPhotos());
    }

    public function testAddPhoto(): void
    {
        $photo = $this->createMockPhoto();
        
        $this->user->addPhoto($photo);
        
        $this->assertCount(1, $this->user->getPhotos());
        $this->assertTrue($this->user->getPhotos()->contains($photo));
        $this->assertSame($this->user, $photo->getUser());
    }

    public function testAddSamePhotoOnlyOnce(): void
    {
        $photo = $this->createMockPhoto();
        
        $this->user->addPhoto($photo);
        $this->user->addPhoto($photo);
        
        $this->assertCount(1, $this->user->getPhotos());
    }

    public function testRemovePhoto(): void
    {
        $photo = $this->createMockPhoto();
        
        $this->user->addPhoto($photo);
        
        $this->assertCount(1, $this->user->getPhotos());
        $this->assertTrue($this->user->getPhotos()->contains($photo));
        
        $this->user->removePhoto($photo);
        $this->assertCount(0, $this->user->getPhotos());
        $this->assertFalse($this->user->getPhotos()->contains($photo));
    }

    public function testRemoveNonExistentPhoto(): void
    {
        $photo1 = $this->createMockPhoto();
        $photo2 = $this->createMockPhoto();
        
        $this->user->addPhoto($photo1);
        
        $this->assertCount(1, $this->user->getPhotos());
        $this->assertTrue($this->user->getPhotos()->contains($photo1));
        $this->assertFalse($this->user->getPhotos()->contains($photo2));
        
        $this->user->removePhoto($photo2);
        $this->assertCount(1, $this->user->getPhotos());
        $this->assertTrue($this->user->getPhotos()->contains($photo1));
        
        $this->user->removePhoto($photo1);
        $this->assertCount(0, $this->user->getPhotos());
    }

    public function testConstructorInitializesPhotosCollection(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(ArrayCollection::class, $user->getPhotos());
        $this->assertCount(0, $user->getPhotos());
    }

    public function testUserCreationWithAllProperties(): void
    {
        $user = new User();
        $user->setUsername('johndoe');
        $user->setEmail('john@example.com');
        $user->setName('John');
        $user->setLastName('Doe');
        $user->setAge(25);
        $user->setBio('Software Developer');

        $this->assertEquals('johndoe', $user->getUsername());
        $this->assertEquals('john@example.com', $user->getEmail());
        $this->assertEquals('John', $user->getName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals(25, $user->getAge());
        $this->assertEquals('Software Developer', $user->getBio());
    }

    public function testPhoenixAccessTokenSettersAndGetters(): void
    {
        $token = 'test-phoenix-access-token-12345';
        
        $this->user->setPhoenixAccessToken($token);
        $this->assertEquals($token, $this->user->getPhoenixAccessToken());
    }

    public function testPhoenixAccessTokenCanBeNull(): void
    {
        $this->user->setPhoenixAccessToken(null);
        $this->assertNull($this->user->getPhoenixAccessToken());
    }

    public function testPhoenixAccessTokenCanBeEmptyString(): void
    {
        $this->user->setPhoenixAccessToken('');
        $this->assertEquals('', $this->user->getPhoenixAccessToken());
    }

    public function testUserCreationWithPhoenixToken(): void
    {
        $user = new User();
        $user->setUsername('johndoe');
        $user->setEmail('john@example.com');
        $user->setPhoenixAccessToken('phoenix-token-abc123');

        $this->assertEquals('johndoe', $user->getUsername());
        $this->assertEquals('john@example.com', $user->getEmail());
        $this->assertEquals('phoenix-token-abc123', $user->getPhoenixAccessToken());
    }
}
