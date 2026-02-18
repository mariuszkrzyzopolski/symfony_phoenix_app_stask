<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PhoenixApiService;
use App\Tests\Fixtures\PhoenixApiResponseFixtures;
use App\Tests\Mock\PhoenixApiMock;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PhoenixApiServiceTest extends TestCase
{
    private PhoenixApiMock $httpClient;
    private PhoenixApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->httpClient = new PhoenixApiMock();
        $this->service = new PhoenixApiService($this->httpClient, 'http://phoenix-api.test');
    }

    public function testSuccessfulPhotoImport(): void
    {
        $token = PhoenixApiResponseFixtures::getValidToken();
        $expectedResponse = PhoenixApiResponseFixtures::getSuccessfulPhotosResponse();
        
        $this->httpClient->setResponse(
            'http://phoenix-api.test/api/photos',
            $expectedResponse,
            200
        );

        $result = $this->service->importPhotos($token);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['photos']);
        $this->assertEquals('https://example.com/photo1.jpg', $result['photos'][0]['photo_url']);
        $this->assertEquals('https://example.com/photo2.jpg', $result['photos'][1]['photo_url']);
        $this->assertEquals('https://example.com/photo3.jpg', $result['photos'][2]['photo_url']);

        $requests = $this->httpClient->getRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals('GET', $requests[0]['method']);
        $this->assertEquals('http://phoenix-api.test/api/photos', $requests[0]['url']);
        $this->assertEquals($token, $requests[0]['options']['headers']['access-token']);
    }

    public function testUnauthorizedToken(): void
    {
        $token = PhoenixApiResponseFixtures::getInvalidToken();
        $errorResponse = PhoenixApiResponseFixtures::getUnauthorizedResponse();
        
        $this->httpClient->setResponse(
            'http://phoenix-api.test/api/photos',
            $errorResponse,
            401
        );

        $result = $this->service->importPhotos($token);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid or expired token', $result['error']);
        $this->assertEmpty($result['photos']);
    }

    public function testEmptyPhotosResponse(): void
    {
        $token = PhoenixApiResponseFixtures::getValidToken();
        $emptyResponse = PhoenixApiResponseFixtures::getEmptyPhotosResponse();
        
        $this->httpClient->setResponse(
            'http://phoenix-api.test/api/photos',
            $emptyResponse,
            200
        );

        $result = $this->service->importPhotos($token);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['photos']);
        $this->assertEquals('No photos found', $result['message']);
    }

    public function testServerError(): void
    {
        $token = PhoenixApiResponseFixtures::getValidToken();
        $errorResponse = PhoenixApiResponseFixtures::getServerErrorResponse();
        
        $this->httpClient->setResponse(
            'http://phoenix-api.test/api/photos',
            $errorResponse,
            500
        );

        $result = $this->service->importPhotos($token);

        $this->assertFalse($result['success']);
        $this->assertEquals('Server error occurred', $result['error']);
        $this->assertEmpty($result['photos']);
    }

    public function testConnectionError(): void
    {
        $token = PhoenixApiResponseFixtures::getValidToken();
        
        // Don't set any response to simulate connection error
        // The service should catch the exception and return an error array
        $result = $this->service->importPhotos($token);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('photos', $result);
        $this->assertEmpty($result['photos']);
    }

    public function testMalformedResponse(): void
    {
        $token = PhoenixApiResponseFixtures::getValidToken();
        
        $this->httpClient->setResponse(
            'http://phoenix-api.test/api/photos',
            ['data' => PhoenixApiResponseFixtures::getMalformedResponse()],
            200
        );

        $result = $this->service->importPhotos($token);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid response format', $result['error']);
        $this->assertEmpty($result['photos']);
    }

    public function testValidateTokenWithValidToken(): void
    {
        $token = PhoenixApiResponseFixtures::getValidToken();
        $expectedResponse = PhoenixApiResponseFixtures::getSuccessfulPhotosResponse();
        
        $this->httpClient->setResponse(
            'http://phoenix-api.test/api/photos',
            $expectedResponse,
            200
        );

        $result = $this->service->validateToken($token);

        $this->assertTrue($result);
    }

    public function testValidateTokenWithInvalidToken(): void
    {
        $token = PhoenixApiResponseFixtures::getInvalidToken();
        $errorResponse = PhoenixApiResponseFixtures::getUnauthorizedResponse();
        
        $this->httpClient->setResponse(
            'http://phoenix-api.test/api/photos',
            $errorResponse,
            401
        );

        $result = $this->service->validateToken($token);

        $this->assertFalse($result);
    }

    public function testValidateTokenWithConnectionError(): void
    {
        $token = PhoenixApiResponseFixtures::getValidToken();
        
        $this->httpClient->setResponse(
            'http://phoenix-api.test/api/photos',
            [],
            500
        );
        
        $result = $this->service->validateToken($token);
        
        $this->assertFalse($result);
    }

    public function testServiceConstructor(): void
    {
        $service = new PhoenixApiService($this->httpClient, 'https://custom-api.example.com');
        
        $this->assertInstanceOf(PhoenixApiService::class, $service);
    }
}
