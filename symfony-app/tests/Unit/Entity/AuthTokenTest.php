<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\AuthToken;
use App\Entity\User;
use App\Tests\Unit\TestCase;

class AuthTokenTest extends TestCase
{
    private AuthToken $authToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authToken = new AuthToken();
    }

    public function testGetIdReturnsNullInitially(): void
    {
        $this->assertNull($this->authToken->getId());
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $before = new \DateTime();
        $token = new AuthToken();
        $after = new \DateTime();
        
        $this->assertGreaterThanOrEqual($before, $token->getCreatedAt());
        $this->assertLessThanOrEqual($after, $token->getCreatedAt());
    }

    public function testTokenSettersAndGetters(): void
    {
        $token = 'abc123def456';
        
        $this->authToken->setToken($token);
        $this->assertEquals($token, $this->authToken->getToken());
    }

    public function testUserSettersAndGetters(): void
    {
        $user = $this->createMockUser();
        
        $this->authToken->setUser($user);
        $this->assertEquals($user, $this->authToken->getUser());
    }

    public function testCreatedAtSettersAndGetters(): void
    {
        $createdAt = new \DateTime('2026-02-02 10:00:00');
        
        $this->authToken->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $this->authToken->getCreatedAt());
    }

    public function testAuthTokenCreationWithAllProperties(): void
    {
        $user = $this->createMockUser();
        $token = 'secure-token-123';
        $createdAt = new \DateTime('2026-02-02 10:00:00');
        
        $this->authToken->setToken($token);
        $this->authToken->setUser($user);
        $this->authToken->setCreatedAt($createdAt);

        $this->assertEquals($token, $this->authToken->getToken());
        $this->assertEquals($user, $this->authToken->getUser());
        $this->assertEquals($createdAt, $this->authToken->getCreatedAt());
    }

    public function testTokenCanBeUpdated(): void
    {
        $this->authToken->setToken('initial-token');
        $this->assertEquals('initial-token', $this->authToken->getToken());

        $this->authToken->setToken('updated-token');
        $this->assertEquals('updated-token', $this->authToken->getToken());
    }

    public function testCreatedAtCanBeUpdated(): void
    {
        $originalDate = $this->authToken->getCreatedAt();
        
        $newDate = new \DateTime('2026-01-01 00:00:00');
        $this->authToken->setCreatedAt($newDate);
        
        $this->assertEquals($newDate, $this->authToken->getCreatedAt());
        $this->assertNotEquals($originalDate, $this->authToken->getCreatedAt());
    }

    public function testUserAssociation(): void
    {
        $user1 = $this->createMockUser(['id' => 1, 'username' => 'user1']);
        $user2 = $this->createMockUser(['id' => 2, 'username' => 'user2']);
        
        $this->authToken->setUser($user1);
        $this->assertEquals($user1, $this->authToken->getUser());
        
        $this->authToken->setUser($user2);
        $this->assertEquals($user2, $this->authToken->getUser());
        $this->assertNotEquals($user1, $this->authToken->getUser());
    }

    public function testAuthTokenWithMinimalData(): void
    {
        $user = $this->createMockUser();
        
        $this->authToken->setUser($user);
        $this->authToken->setToken('minimal-token');

        $this->assertEquals($user, $this->authToken->getUser());
        $this->assertNotNull($this->authToken->getCreatedAt());
        $this->assertEquals('minimal-token', $this->authToken->getToken());
    }

    public function testTokenValueTypes(): void
    {
        $stringToken = 'string-token';
        $numericToken = '123456';
        $alphanumericToken = 'abc123xyz456';
        
        $this->authToken->setToken($stringToken);
        $this->assertEquals($stringToken, $this->authToken->getToken());
        
        $this->authToken->setToken($numericToken);
        $this->assertEquals($numericToken, $this->authToken->getToken());
        
        $this->authToken->setToken($alphanumericToken);
        $this->assertEquals($alphanumericToken, $this->authToken->getToken());
    }
}
