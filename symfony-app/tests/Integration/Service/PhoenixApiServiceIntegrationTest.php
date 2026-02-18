<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Service\PhoenixApiService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PhoenixApiServiceIntegrationTest extends KernelTestCase
{
    private HttpClientInterface $httpClient;
    private PhoenixApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->httpClient = new MockHttpClient();
        $this->service = new PhoenixApiService($this->httpClient, 'http://phoenix-api.test');
    }

    public function testCommunicationWithValidToken(): void
    {
        $responses = [
            new MockResponse(json_encode([
                'photos' => [
                    ['id' => 1, 'photo_url' => 'https://example.com/photo1.jpg'],
                    ['id' => 2, 'photo_url' => 'https://example.com/photo2.jpg']
                ]
            ]), ['http_code' => 200])
        ];

        $this->httpClient = new MockHttpClient($responses, 'http://phoenix-api.test');
        $this->service = new PhoenixApiService($this->httpClient, 'http://phoenix-api.test');

        $result = $this->service->fetchPhotosFromApi('valid-token');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['photos']);
        $this->assertEquals('https://example.com/photo1.jpg', $result['photos'][0]['photo_url']);
    }

    public function testPhotoImportWithRealData(): void
    {
        $responses = [
            new MockResponse(json_encode([
                'photos' => [
                    [
                        'id' => 1,
                        'photo_url' => 'https://picsum.photos/seed/photo1/800/600.jpg'
                    ],
                    [
                        'id' => 2,
                        'photo_url' => 'https://picsum.photos/seed/photo2/800/600.jpg'
                    ],
                    [
                        'id' => 3,
                        'photo_url' => 'https://picsum.photos/seed/photo3/800/600.jpg'
                    ]
                ]
            ]), ['http_code' => 200])
        ];

        $this->httpClient = new MockHttpClient($responses, 'http://phoenix-api.test');
        $this->service = new PhoenixApiService($this->httpClient, 'http://phoenix-api.test');

        $result = $this->service->fetchPhotosFromApi('real-token');

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['photos']);
        
        foreach ($result['photos'] as $photo) {
            $this->assertArrayHasKey('id', $photo);
            $this->assertArrayHasKey('photo_url', $photo);
            $this->assertIsString($photo['photo_url']);
            $this->assertStringStartsWith('https://', $photo['photo_url']);
        }
    }

    public function testApiErrorHandling(): void
    {
        $responses = [
            new MockResponse(json_encode([
                'errors' => ['detail' => 'Unauthorized']
            ]), ['http_code' => 401])
        ];

        $this->httpClient = new MockHttpClient($responses, 'http://phoenix-api.test');
        $this->service = new PhoenixApiService($this->httpClient, 'http://phoenix-api.test');

        $result = $this->service->fetchPhotosFromApi('invalid-token');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid or expired token', $result['error']);
    }

    public function testApiTimeoutHandling(): void
    {
        $responses = [
            new MockResponse('', ['http_code' => 200, 'timeout' => 0.001])
        ];

        $this->httpClient = new MockHttpClient($responses, 'http://phoenix-api.test');
        $this->service = new PhoenixApiService($this->httpClient, 'http://phoenix-api.test');

        // The service should handle timeout gracefully and return an error array
        $result = $this->service->fetchPhotosFromApi('timeout-token');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('photos', $result);
        $this->assertEmpty($result['photos']);
    }

    public function testServiceWithDifferentBaseUrls(): void
    {
        $responses = [
            new MockResponse(json_encode(['photos' => []]), ['http_code' => 200])
        ];

        $this->httpClient = new MockHttpClient($responses, 'https://custom-phoenix.example.com');
        $service = new PhoenixApiService($this->httpClient, 'https://custom-phoenix.example.com');

        $result = $service->fetchPhotosFromApi('test-token');

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['photos']);
    }
}
