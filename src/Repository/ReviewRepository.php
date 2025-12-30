<?php

namespace App\Repository;

use App\Document\Review;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class ReviewRepository
{
    private DocumentManager $dm;
    private DocumentRepository $repository;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->repository = $dm->getRepository(Review::class);
    }

    public function save(Review $review): void
    {
        $this->dm->persist($review);
        $this->dm->flush();
    }

    public function findByGameId(int $gameId): array
    {
        return $this->repository->findBy(
            ['gameId' => $gameId],
            ['createdAt' => 'DESC']
        );
    }

    public function findByUserId(int $userId): array
    {
        return $this->repository->findBy(
            ['userId' => $userId],
            ['createdAt' => 'DESC']
        );
    }

    public function findOneById(string $id): ?Review
    {
        return $this->repository->find($id);
    }

    public function getAverageRatingForGame(int $gameId): float
    {
        $reviews = $this->findByGameId($gameId);
        
        if (count($reviews) === 0) {
            return 0.0;
        }

        $totalRating = 0;
        foreach ($reviews as $review) {
            $totalRating += $review->getRating();
        }

        return round($totalRating / count($reviews), 1);
    }

    public function countByGameId(int $gameId): int
    {
        return $this->repository->count(['gameId' => $gameId]);
    }

    public function delete(Review $review): void
    {
        $this->dm->remove($review);
        $this->dm->flush();
    }
}