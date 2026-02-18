<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PhoenixApiService
{
    private HttpClientInterface $httpClient;
    private string $baseUrl;

    public function __construct(HttpClientInterface $httpClient, string $baseUrl)
    {
        $this->httpClient = $httpClient;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function fetchPhotosFromApi(string $token): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/api/photos', [
                'headers' => [
                    'access-token' => $token,
                    'Accept' => 'application/json',
                ],
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();
            
            switch ($statusCode) {
                case 200:
                    break;
                    
                case 401:
                    return [
                        'success' => false,
                        'error' => 'Invalid or expired token',
                        'photos' => []
                    ];
                    
                case 429:
                    return [
                        'success' => false,
                        'error' => 'Rate limit exceeded',
                        'photos' => []
                    ];
                    
                default:
                    if ($statusCode >= 500) {
                        return [
                            'success' => false,
                            'error' => 'Server error occurred',
                            'photos' => []
                        ];
                    }
                    
                    return [
                        'success' => false,
                        'error' => 'Unexpected response from server',
                        'photos' => []
                    ];
            }

            $data = $response->toArray();
            
            if (!isset($data['photos'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid response format',
                    'photos' => []
                ];
            }

            $photos = $data['photos'];
            
            if (empty($photos)) {
                return [
                    'success' => true,
                    'message' => 'No photos found',
                    'photos' => []
                ];
            }

            return [
                'success' => true,
                'message' => count($photos) . ' photos imported successfully',
                'photos' => $photos
            ];

        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Failed to connect to Phoenix API',
                'photos' => []
            ];
        } catch (ClientExceptionInterface | ServerExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'API request failed',
                'photos' => []
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'An unexpected error occurred',
                'photos' => []
            ];
        }
    }
}
