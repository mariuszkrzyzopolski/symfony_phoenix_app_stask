<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Photo;
use App\Entity\User;
use App\Likes\Like;
use App\Likes\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\TestCase;

class LikeRepositoryTest extends TestCase
{
    private LikeRepository $repository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->entityManager = TestCase::getTestContainer()
            ->get('doctrine')
            ->getManager();
        
        $this->repository = $this->entityManager->getRepository(Like::class);
        
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

    private function createUser(string $username = 'testuser'): User
    {
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
        $photo->setLocation('Test Location');
        $photo->setDescription('Test Description');
        $photo->setCamera('Test Camera');
        $photo->setTakenAt(new \DateTimeImmutable('2026-02-02 08:00:00'));
        $photo->setLikeCounter(0);
        $photo->setUser($user);
        
        $this->entityManager->persist($photo);
        $this->entityManager->flush();
        
        return $photo;
    }

    public function testCreateLike(): void
    {
        $user = $this->createUser('liker');
        $photo = $this->createPhoto($user);
        
        $this->repository->setUser($user);
        $like = $this->repository->createLike($photo);
        
        $this->assertInstanceOf(Like::class, $like);
        $this->assertSame($user, $like->getUser());
        $this->assertSame($photo, $like->getPhoto());
        $this->assertInstanceOf(\DateTimeInterface::class, $like->getCreatedAt());
        $persistedLikes = $this->repository->findAll();
        $this->assertCount(1, $persistedLikes);
        $persistedLike = $persistedLikes[0];
        $this->assertSame($user, $persistedLike->getUser());
        $this->assertSame($photo, $persistedLike->getPhoto());
    }

    public function testHasUserLikedPhotoReturnsFalseWhenNoLike(): void
    {
        $user = $this->createUser('liker');
        $photo = $this->createPhoto($user);
        
        $this->repository->setUser($user);
        $hasLiked = $this->repository->hasUserLikedPhoto($photo);
        
        $this->assertFalse($hasLiked);
    }

    public function testHasUserLikedPhotoReturnsTrueWhenLikeExists(): void
    {
        $user = $this->createUser('liker');
        $photo = $this->createPhoto($user);
        
        $this->repository->setUser($user);
        $this->repository->createLike($photo);
        
        $hasLiked = $this->repository->hasUserLikedPhoto($photo);
        
        $this->assertTrue($hasLiked);
    }

    public function testUnlikePhotoRemovesLikeAndDecrementsCounter(): void
    {
        $user = $this->createUser('liker');
        $photo = $this->createPhoto($user);
        $photo->setLikeCounter(5);
        $this->entityManager->persist($photo);
        $this->entityManager->flush();
        
        $this->repository->setUser($user);
        $this->repository->createLike($photo);
        
        // TODO: Check if createLike should increment counter
        $this->assertTrue($this->repository->hasUserLikedPhoto($photo));
        $this->assertEquals(5, $photo->getLikeCounter());
        
        $this->repository->unlikePhoto($photo);
        $this->assertFalse($this->repository->hasUserLikedPhoto($photo));
        
        // Refresh photo from database to get updated counter
        $this->entityManager->refresh($photo);
        $this->assertEquals(4, $photo->getLikeCounter());
    }

    public function testUnlikePhotoDoesNothingWhenNoLikeExists(): void
    {
        $user = $this->createUser('liker');
        $photo = $this->createPhoto($user);
        $photo->setLikeCounter(3);
        $this->entityManager->persist($photo);
        $this->entityManager->flush();
        
        $this->repository->setUser($user);
        $this->assertFalse($this->repository->hasUserLikedPhoto($photo));
        
        $this->repository->unlikePhoto($photo);
        
        $this->entityManager->refresh($photo);
        $this->assertEquals(3, $photo->getLikeCounter());
    }

    public function testUpdatePhotoCounter(): void
    {
        $user = $this->createUser('liker');
        $photo = $this->createPhoto($user);
        $photo->setLikeCounter(10);
        $this->entityManager->persist($photo);
        $this->entityManager->flush();
        
        $this->repository->setUser($user);
        $this->repository->updatePhotoCounter($photo, 5);
        
        $this->entityManager->refresh($photo);
        $this->assertEquals(15, $photo->getLikeCounter());
        $this->repository->updatePhotoCounter($photo, -3);
        
        $this->entityManager->refresh($photo);
        $this->assertEquals(12, $photo->getLikeCounter());
    }

    public function testMultipleUsersCanLikeSamePhoto(): void
    {
        $user1 = $this->createUser('user1');
        $user2 = $this->createUser('user2');
        $photo = $this->createPhoto($user1);
        
        $this->repository->setUser($user1);
        $like1 = $this->repository->createLike($photo);
        
        $this->repository->setUser($user2);
        $like2 = $this->repository->createLike($photo);
        
        $this->assertNotNull($like1);
        $this->assertNotNull($like2);
        $this->assertNotSame($like1, $like2);
        
        $this->repository->setUser($user1);
        $this->assertTrue($this->repository->hasUserLikedPhoto($photo));
        
        $this->repository->setUser($user2);
        $this->assertTrue($this->repository->hasUserLikedPhoto($photo));
    }

    public function testUserCanLikeMultiplePhotos(): void
    {
        $user = $this->createUser('multiliker');
        $photo1 = $this->createPhoto($user, 'https://example.com/photo1.jpg');
        $photo2 = $this->createPhoto($user, 'https://example.com/photo2.jpg');
        
        $this->repository->setUser($user);
        
        $like1 = $this->repository->createLike($photo1);
        $like2 = $this->repository->createLike($photo2);
        
        $this->assertNotNull($like1);
        $this->assertNotNull($like2);
        $this->assertNotSame($like1, $like2);
        
        $this->assertTrue($this->repository->hasUserLikedPhoto($photo1));
        $this->assertTrue($this->repository->hasUserLikedPhoto($photo2));
    }

    public function testFindAllLikesForPhoto(): void
    {
        $user1 = $this->createUser('user1');
        $user2 = $this->createUser('user2');
        $user3 = $this->createUser('user3');
        $photo = $this->createPhoto($user1);
        
        $this->repository->setUser($user1);
        $this->repository->createLike($photo);
        
        $this->repository->setUser($user2);
        $this->repository->createLike($photo);
        
        $this->repository->setUser($user3);
        $this->repository->createLike($photo);
        
        $likes = $this->repository->findBy(['photo' => $photo]);
        $this->assertCount(3, $likes);
        
        $usernames = [];
        foreach ($likes as $like) {
            $usernames[] = $like->getUser()->getUsername();
        }
        
        $this->assertContains($user1->getUsername(), $usernames);
        $this->assertContains($user2->getUsername(), $usernames);
        $this->assertContains($user3->getUsername(), $usernames);
    }
}
