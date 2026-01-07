<?php

namespace App\Repository;

use App\Document\Review;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class ReviewRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        parent::__construct($dm, $dm->getUnitOfWork(), $dm->getClassMetadata(Review::class));
    }
    
    /**
     * Sauvegarder un avis
     */
    public function save(Review $review): void
    {
        $this->getDocumentManager()->persist($review);
        $this->getDocumentManager()->flush();
    }
    
    /**
     * Récupérer tous les avis pour un jeu (triés par date décroissante)
     */
    public function findByGameId(int $gameId): array
    {
        return $this->createQueryBuilder()
            ->field('gameId')->equals($gameId)
            ->sort('createdAt', 'desc')
            ->getQuery()
            ->execute()
            ->toArray();
    }
    
    /**
     * Récupérer tous les avis d'un utilisateur (triés par date décroissante)
     */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals($userId)
            ->sort('createdAt', 'desc')
            ->getQuery()
            ->execute()
            ->toArray();
    }
    
    /**
     * Récupérer un avis par son ID
     */
    public function findOneById(string $id): ?Review
    {
        return $this->find($id);
    }
    
    /**
     * Calculer la note moyenne pour un jeu
     */
    public function getAverageRatingForGame(int $gameId): float
    {
        $reviews = $this->findByGameId($gameId);
        
        if (empty($reviews)) {
            return 0.0;
        }
        
        $totalRating = 0;
        foreach ($reviews as $review) {
            $totalRating += $review->getRating();
        }
        
        return round($totalRating / count($reviews), 1);
    }
    
    /**
     * ✅ CORRECTION : Compter le nombre d'avis pour un jeu
     * On ne peut PAS utiliser count() sur DocumentRepository !
     */
    public function countByGameId(int $gameId): int
    {
        return $this->createQueryBuilder()
            ->field('gameId')->equals($gameId)
            ->count()
            ->getQuery()
            ->execute();
    }
    
    /**
     * Supprimer un avis
     */
    public function delete(string $id): void
    {
        $review = $this->find($id);
        if ($review) {
            $this->getDocumentManager()->remove($review);
            $this->getDocumentManager()->flush();
        }
    }
}