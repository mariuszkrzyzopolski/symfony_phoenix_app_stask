<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Photo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u');

        if (!empty($filters['location'])) {
            $qb->andWhere('p.location LIKE :location')
               ->setParameter('location', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['camera'])) {
            $qb->andWhere('p.camera LIKE :camera')
               ->setParameter('camera', '%' . $filters['camera'] . '%');
        }

        if (!empty($filters['description'])) {
            $qb->andWhere('p.description LIKE :description')
               ->setParameter('description', '%' . $filters['description'] . '%');
        }

        if (!empty($filters['username'])) {
            $qb->andWhere('u.username LIKE :username')
               ->setParameter('username', '%' . $filters['username'] . '%');
        }

        if (!empty($filters['taken_at_from'])) {
            $qb->andWhere('p.takenAt >= :taken_at_from')
               ->setParameter('taken_at_from', $filters['taken_at_from']);
        }

        if (!empty($filters['taken_at_to'])) {
            $endDate = $filters['taken_at_to']->setTime(23, 59, 59);
            $qb->andWhere('p.takenAt <= :taken_at_to')
               ->setParameter('taken_at_to', $endDate);
        }

        return $qb->orderBy('p.id', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    public function findAllWithUsers(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
