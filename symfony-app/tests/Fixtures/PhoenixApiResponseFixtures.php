<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

class PhoenixApiResponseFixtures
{
    public static function getSuccessfulPhotosResponse(): array
    {
        return [
            'photos' => [
                [
                    'id' => 1,
                    'photo_url' => 'https://example.com/photo1.jpg'
                ],
                [
                    'id' => 2,
                    'photo_url' => 'https://example.com/photo2.jpg'
                ],
                [
                    'id' => 3,
                    'photo_url' => 'https://example.com/photo3.jpg'
                ]
            ]
        ];
    }

    public static function getEmptyPhotosResponse(): array
    {
        return [
            'photos' => []
        ];
    }

    public static function getUnauthorizedResponse(): array
    {
        return [
            'errors' => [
                'detail' => 'Unauthorized'
            ]
        ];
    }

    public static function getServerErrorResponse(): array
    {
        return [
            'errors' => [
                'detail' => 'Internal Server Error'
            ]
        ];
    }

    public static function getValidToken(): string
    {
        return 'valid-phoenix-token-12345';
    }

    public static function getInvalidToken(): string
    {
        return 'invalid-phoenix-token-67890';
    }

    public static function getMalformedResponse(): string
    {
        return '{"invalid": json}';
    }
}
