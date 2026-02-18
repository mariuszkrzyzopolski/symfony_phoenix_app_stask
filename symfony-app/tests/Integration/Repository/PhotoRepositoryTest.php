<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Photo;
use App\Entity\User;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\TestCase;

class PhotoRepositoryTest extends TestCase
{
    private PhotoRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->entityManager = TestCase::getTestContainer()
            ->get('doctrine')
            ->getManager();
        
        $this->repository = $this->entityManager->getRepository(Photo::class);
        
        $this->entityManager->createQuery('DELETE FROM App\Entity\Photo')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        
        $this->entityManager->close();
    }

    private function createUser(string $username = null): User
    {
        if ($username === null) {
            $username = 'testuser_' . uniqid();
        }
        
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username . '@example.com');
        $user->setName('Test');
        $user->setLastName('User');
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    private function createPhoto(User $user, string $imageUrl = 'https://example.com/photo.jpg'): Photo
    {
        $photo = new Photo();
        $photo->setImageUrl($imageUrl);
        $photo->setUser($user);
        $photo->setDescription('Test photo');
        
        $this->entityManager->persist($photo);
        $this->entityManager->flush();
        
        return $photo;
    }

    public function testFindAllWithUsersReturnsEmptyArrayWhenNoPhotos(): void
    {
        $photos = $this->repository->findAllWithUsers();
        
        $this->assertIsArray($photos);
        $this->assertEmpty($photos);
    }

    public function testFindAllWithUsersReturnsPhotosWithUsers(): void
    {
        $user = $this->createUser();
        $photo = $this->createPhoto($user);
        
        $photos = $this->repository->findAllWithUsers();
        
        $this->assertCount(1, $photos);
        $this->assertInstanceOf(Photo::class, $photos[0]);
        $this->assertEquals($photo->getId(), $photos[0]->getId());
        $this->assertEquals($user->getId(), $photos[0]->getUser()->getId());
        $this->assertEquals($user->getUsername(), $photos[0]->getUser()->getUsername());
    }

    public function testFindAllWithUsersReturnsMultiplePhotosOrderedById(): void
    {
        $user = $this->createUser();
        
        $this->createPhoto($user, 'https://example.com/photo2.jpg');
        $this->createPhoto($user, 'https://example.com/photo1.jpg');
        
        $photos = $this->repository->findAllWithUsers();
        
        $this->assertCount(2, $photos);
        $this->assertLessThan($photos[1]->getId(), $photos[0]->getId());
    }

    public function testFindAllWithUsersIncludesUserDetails(): void
    {
        $user = $this->createUser();
        $this->createPhoto($user);
        
        $photos = $this->repository->findAllWithUsers();
        $resultPhoto = $photos[0];
        $resultUser = $resultPhoto->getUser();
        
        $this->assertEquals($user->getId(), $resultUser->getId());
        $this->assertEquals($user->getUsername(), $resultUser->getUsername());
        $this->assertEquals($user->getEmail(), $resultUser->getEmail());
        $this->assertEquals($user->getName(), $resultUser->getName());
        $this->assertEquals($user->getLastName(), $resultUser->getLastName());
    }

    public function testFindAllWithUsersWithDifferentUsers(): void
    {
        $user1 = $this->createUser('user1');
        $user2 = $this->createUser('user2');
        
        $this->createPhoto($user1, 'https://example.com/photo1.jpg');
        $this->createPhoto($user2, 'https://example.com/photo2.jpg');
        
        $photos = $this->repository->findAllWithUsers();
        
        $this->assertCount(2, $photos);
        
        $photoUsers = array_map(fn($photo) => $photo->getUser()->getUsername(), $photos);
        $this->assertContains('user1', $photoUsers);
        $this->assertContains('user2', $photoUsers);
    }

    public function testFindAllWithUsersPerformance(): void
    {
        $user = $this->createUser();
        
        for ($i = 0; $i < 5; $i++) {
            $this->createPhoto($user, "https://example.com/photo$i.jpg");
        }
        
        $startTime = microtime(true);
        $photos = $this->repository->findAllWithUsers();
        $endTime = microtime(true);
        
        $this->assertCount(5, $photos);
        $this->assertLessThan(1.0, $endTime - $startTime, 'Query should be efficient');
        
        foreach ($photos as $photo) {
            $this->assertNotNull($photo->getUser(), 'User should be loaded without additional queries');
        }
    }
}
