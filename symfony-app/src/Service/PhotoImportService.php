<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PhotoImportService
{
    public function __construct(
        private PhoenixApiService $phoenixApiService,
        private EntityManagerInterface $em
    ) {}

    public function validateToken(string $token): array
    {
        $errors = [];
        
        if (empty($token)) {
            $errors[] = 'API access token is required';
        } elseif (strlen($token) > 1000) {
            $errors[] = 'API access token is too long';
        } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $token)) {
            $errors[] = 'API access token contains invalid characters';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    public function importPhotosFromPhoenix(string $token, User $user): array
    {
        try {
            $result = $this->phoenixApiService->fetchPhotosFromApi($token);
            
            if (!$result['success']) {
                return ['success' => false, 'error' => $result['error']];
            }

            $importedCount = 0;
            foreach ($result['photos'] as $photoData) {
                if ($this->savePhotoToDatabase($photoData, $user)) {
                    $importedCount++;
                }
            }
            
            $this->em->flush();
            
            return [
                'success' => true,
                'message' => isset($result['message']) 
                    ? $result['message'] 
                    : "Successfully imported {$importedCount} photos"
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to import photos: ' . $e->getMessage()];
        }
    }

    private function savePhotoToDatabase(array $photoData, User $user): bool
    {
        $existingPhoto = $this->em->getRepository(Photo::class)->findOneBy([
            'user' => $user,
            'imageUrl' => $photoData['photo_url']
        ]);
        
        if ($existingPhoto) {
            return false;
        }

        $photoUrl = $photoData['photo_url'];
        
        if (!filter_var($photoUrl, FILTER_VALIDATE_URL) || strlen($photoUrl) > 2048) {
            return false;
        }

        $photo = new Photo();
        $photo->setImageUrl($photoUrl);
        $photo->setUser($user);
        $this->em->persist($photo);
        
        return true;
    }
}
