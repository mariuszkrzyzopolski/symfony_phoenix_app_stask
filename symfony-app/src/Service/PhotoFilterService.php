<?php

declare(strict_types=1);

namespace App\Service;

class PhotoFilterService
{
    public function processFilters(array $parameters): array
    {
        $filters = [];

        if (!empty($parameters['location'])) {
            $location = trim($parameters['location']);
            if ($location !== '' && strlen($location) <= 255) {
                $filters['location'] = $location;
            }
        }

        if (!empty($parameters['camera'])) {
            $camera = trim($parameters['camera']);
            if ($camera !== '' && strlen($camera) <= 255) {
                $filters['camera'] = $camera;
            }
        }

        if (!empty($parameters['description'])) {
            $description = trim($parameters['description']);
            if ($description !== '' && strlen($description) <= 1000) {
                $filters['description'] = $description;
            }
        }

        if (!empty($parameters['username'])) {
            $username = trim($parameters['username']);
            if ($username !== '' && strlen($username) <= 180) {
                $filters['username'] = $username;
            }
        }

        if (!empty($parameters['taken_at_from'])) {
            $fromDate = $this->validateDate($parameters['taken_at_from']);
            if ($fromDate !== null) {
                $filters['taken_at_from'] = $fromDate;
            }
        }

        if (!empty($parameters['taken_at_to'])) {
            $toDate = $this->validateDate($parameters['taken_at_to']);
            if ($toDate !== null) {
                $filters['taken_at_to'] = $toDate;
            }
        }

        return $filters;
    }

    private function validateDate(string $date): ?\DateTimeImmutable
    {
        try {
            $dateTime = new \DateTimeImmutable($date);
            if (strpos($date, ' ') === false) {
                return $dateTime->setTime(0, 0, 0);
            }
            return $dateTime;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function hasActiveFilters(array $filters): bool
    {
        return !empty($filters);
    }

    public function getFilterSummary(array $filters): array
    {
        $summary = [];

        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'location':
                    $summary[] = "Location: " . htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    break;
                case 'camera':
                    $summary[] = "Camera: " . htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    break;
                case 'description':
                    $summary[] = "Description contains: " . htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    break;
                case 'username':
                    $summary[] = "Username: " . htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    break;
                case 'taken_at_from':
                    $summary[] = "From: " . $value->format('Y-m-d');
                    break;
                case 'taken_at_to':
                    $summary[] = "To: " . $value->format('Y-m-d');
                    break;
            }
        }

        return $summary;
    }
}
