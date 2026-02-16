<?php

namespace App\Controller;

use App\Repository\UserGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, UserGameRepository $userGameRepository): Response
    {
        $user = $this->getUser();

        // Récupérer les filtres depuis GET
        $statusFilter = $request->query->get('status', 'all');
        $genreFilter = $request->query->get('genre', 'all');

        // Récupérer tous les jeux de l'utilisateur
        $userGames = $userGameRepository->findBy(['user' => $user], ['addedAt' => 'DESC']);

        // Filtrer par statut si besoin
        if ($statusFilter !== 'all') {
            $userGames = array_filter($userGames, fn($userGame) => $userGame->getStatus() === $statusFilter);
        }

        // Filtrer par genre si besoin (insensible à la casse)
        if ($genreFilter !== 'all') {
            $userGames = array_filter($userGames, function($userGame) use ($genreFilter) {
                $genres = $userGame->getGame()->getGenres() ?? [];
                return in_array($genreFilter, $genres, true);
            });
        }

        // Récupérer tous les genres disponibles pour le select
        $allGenres = [];
        foreach ($user->getUserGames() as $ug) {
            $genres = $ug->getGame()->getGenres() ?? [];
            foreach ($genres as $g) {
                if (!in_array($g, $allGenres, true)) {
                    $allGenres[] = $g;
                }
            }
        }
        sort($allGenres);

        // Statistiques
        $allUserGames = $user->getUserGames();
        $stats = [
            'total' => count($allUserGames),
            'backlog' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];
        foreach ($allUserGames as $ug) {
            $status = $ug->getStatus();
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $this->render('home/index.html.twig', [
            'userGames' => $userGames,
            'stats' => $stats,
            'allGenres' => $allGenres,
            'statusFilter' => $statusFilter,
            'genreFilter' => $genreFilter,
        ]);
    }

    #[Route('/change-status/{id}', name: 'app_home_change_status', methods: ['POST'])]
    public function changeStatus(int $id, Request $request, UserGameRepository $userGameRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $userGame = $userGameRepository->find($id);

        if ($userGame === null || $userGame->getUser() !== $user) {
            $this->addFlash('error', 'Jeu introuvable ou non autorisé.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        $newStatus = $request->request->get('status');
        $validStatuses = ['backlog', 'in_progress', 'completed'];

        if (!in_array($newStatus, $validStatuses, true)) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        $userGame->setStatus($newStatus);
        $entityManager->flush();

        $statusMessages = [
            'backlog' => '📚 Jeu ajouté À commencer',
            'in_progress' => '🎮 Jeu marqué comme en cours',
            'completed' => '✅ Jeu marqué comme terminé',
        ];
        $this->addFlash('success', $statusMessages[$newStatus]);

        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
    }

    #[Route('/toggle-favorite/{id}', name: 'app_home_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(int $id, Request $request, UserGameRepository $userGameRepository, EntityManagerInterface $entityManager): Response
    {
        $userGame = $userGameRepository->find($id);

        if ($userGame === null || $userGame->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce jeu');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        $userGame->setIsFavorite(!$userGame->isFavorite());
        $entityManager->flush();

        $this->addFlash('success', $userGame->isFavorite() ? '❤️ Jeu ajouté aux favoris' : '💔 Jeu retiré des favoris');

        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
    }

    #[Route('/favorites', name: 'app_favorites')]
    public function favorites(UserGameRepository $userGameRepository): Response
    {
        $user = $this->getUser();
        $favoriteGames = $userGameRepository->findBy(['user' => $user, 'isFavorite' => true], ['addedAt' => 'DESC']);

        $allUserGames = $user->getUserGames();
        $stats = [
            'total' => count($allUserGames),
            'backlog' => 0,
            'in_progress' => 0,
            'completed' => 0
        ];
        foreach ($allUserGames as $userGame) {
            $status = $userGame->getStatus();
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $this->render('favorites/index.html.twig', [
            'favoriteGames' => $favoriteGames,
            'stats' => $stats
        ]);
    }

    #[Route('/delete-game/{id}', name: 'app_home_delete_game', methods: ['POST'])]
    public function deleteGame(int $id, Request $request, UserGameRepository $userGameRepository, EntityManagerInterface $entityManager): Response
    {
        $userGame = $userGameRepository->find($id);

        if ($userGame === null || $userGame->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce jeu');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        $gameName = $userGame->getGame()->getName();
        $entityManager->remove($userGame);
        $entityManager->flush();

        $this->addFlash('success', '🗑️ "' . $gameName . '" a été retiré de votre bibliothèque');

        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
    }
}
