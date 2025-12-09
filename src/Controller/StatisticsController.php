<?php

namespace App\Controller;

use App\Repository\UserGameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatisticsController extends AbstractController
{
    #[Route('/statistics', name: 'app_statistics')]
    public function index(UserGameRepository $userGameRepository): Response
    {
        $user = $this->getUser();
        $allUserGames = $userGameRepository->findBy(['user' => $user]);

        // Statistiques générales
        $stats = [
            'total' => count($allUserGames),
            'backlog' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'favorites' => 0,
        ];

        // Calculer les stats de base
        foreach ($allUserGames as $userGame) {
            $status = $userGame->getStatus();
            if ($status === 'backlog') {
                $stats['backlog']++;
            }
            if ($status === 'in_progress') {
                $stats['in_progress']++;
            }
            if ($status === 'completed') {
                $stats['completed']++;
            }
            if ($userGame->isFavorite()) {
                $stats['favorites']++;
            }
        }

        // Pourcentage de complétion
        $completionRate = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;

        // Statistiques par genre
        $genreStats = [];
        foreach ($allUserGames as $userGame) {
            $genres = $userGame->getGame()->getGenres();
            if ($genres) {
                foreach ($genres as $genre) {
                    if (isset($genreStats[$genre]) === false) {
                        $genreStats[$genre] = 0;
                    }
                    $genreStats[$genre]++;
                }
            }
        }
        arsort($genreStats);
        $topGenres = array_slice($genreStats, 0, 5, true);

        // Top 3 jeux les mieux notés
        $topRatedGames = [];
        foreach ($allUserGames as $userGame) {
            $rating = $userGame->getGame()->getRating();
            if ($rating !== null) {
                $topRatedGames[] = $userGame;
            }
        }
        usort($topRatedGames, function ($a, $b) {
            return $b->getGame()->getRating() <=> $a->getGame()->getRating();
        });
        $topRatedGames = array_slice($topRatedGames, 0, 3);

        // Jeux ajoutés par mois (6 derniers mois)
        $monthlyStats = [];
        $now = new \DateTime();
        for ($i = 5; $i >= 0; $i--) {
            $month = clone $now;
            $month->modify("-$i month");
            $monthLabel = $month->format('M Y');
            $monthlyStats[$monthLabel] = 0;
        }

        foreach ($allUserGames as $userGame) {
            $addedMonth = $userGame->getAddedAt()->format('M Y');
            if (isset($monthlyStats[$addedMonth])) {
                $monthlyStats[$addedMonth]++;
            }
        }

        return $this->render('statistics/index.html.twig', [
            'stats' => $stats,
            'completionRate' => $completionRate,
            'topGenres' => $topGenres,
            'topRatedGames' => $topRatedGames,
            'monthlyStats' => $monthlyStats,
        ]);
    }
}