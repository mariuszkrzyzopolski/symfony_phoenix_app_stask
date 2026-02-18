<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Photo;
use App\Repository\PhotoRepository;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;

class HomeDataService
{
    public function __construct(
        private PhotoRepository $photoRepository,
        private LikeRepository $likeRepository,
        private PhotoFilterService $filterService,
        private EntityManagerInterface $em
    ) {}

    public function getHomeData(array $queryParams, ?int $userId): array
    {
        $photos = $this->getPhotos($queryParams);
        $currentUser = $this->getCurrentUser($userId);
        $userLikes = $this->getUserLikes($photos, $currentUser);
        $filters = $this->filterService->processFilters($queryParams);

        return [
            'photos' => $photos,
            'currentUser' => $currentUser,
            'userLikes' => $userLikes,
            'filters' => $filters,
            'filterSummary' => $this->filterService->getFilterSummary($filters),
            'hasActiveFilters' => $this->filterService->hasActiveFilters($filters),
        ];
    }

    private function getPhotos(array $queryParams): array
    {
        $filters = $this->filterService->processFilters($queryParams);
        
        if ($this->filterService->hasActiveFilters($filters)) {
            return $this->photoRepository->findByFilters($filters);
        }
        
        return $this->photoRepository->findAllWithUsers();
    }

    private function getCurrentUser(?int $userId): ?User
    {
        if (!$userId) {
            return null;
        }

        return $this->em->getRepository(User::class)->find($userId);
    }

    private function getUserLikes(array $photos, ?User $currentUser): array
    {
        if (!$currentUser) {
            return [];
        }

        $userLikes = [];
        $this->likeRepository->setUser($currentUser);

        foreach ($photos as $photo) {
            $userLikes[$photo->getId()] = $this->likeRepository->hasUserLikedPhoto($photo);
        }

        return $userLikes;
    }
}
