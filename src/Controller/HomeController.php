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
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Récupérer les paramètres de filtre
        $statusFilter = $request->query->get('status', 'all');
        $genreFilter = $request->query->get('genre', 'all');

        // Récupérer tous les jeux de l'utilisateur
        $userGames = $userGameRepository->findBy(
            ['user' => $user],
            ['addedAt' => 'DESC']
        );

        // Filtrer par statut
        if ($statusFilter !== 'all') {
            $userGames = array_filter($userGames, function ($userGame) use ($statusFilter) {
                return $userGame->getStatus() === $statusFilter;
            });
        }

        // Filtrer par genre
        if ($genreFilter !== 'all') {
            $userGames = array_filter($userGames, function ($userGame) use ($genreFilter) {
                $genres = $userGame->getGame()->getGenres();
                if ($genres) {
                    foreach ($genres as $genre) {
                        if ($genre === $genreFilter) {
                            return true;
                        }
                    }
                }
                return false;
            });
        }

        // Récupérer tous les genres disponibles pour le filtre
        $allGenres = [];
        foreach ($userGameRepository->findBy(['user' => $user]) as $userGame) {
            $genres = $userGame->getGame()->getGenres();
            if ($genres) {
                foreach ($genres as $genre) {
                    if (!in_array($genre, $allGenres)) {
                        $allGenres[] = $genre;
                    }
                }
            }
        }
        sort($allGenres);

        // Calculer les statistiques (sur TOUS les jeux, pas les filtrés)
        $allUserGames = $userGameRepository->findBy(['user' => $user]);
        $stats = [
            'total' => count($allUserGames),
            'backlog' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];

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
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Récupérer le UserGame par son ID
        $userGame = $userGameRepository->find($id);

        // Vérifier que le UserGame existe et appartient à l'utilisateur connecté
        if ($userGame === null || $userGame->getUser() !== $user) {
            $this->addFlash('error', 'Jeu introuvable ou non autorisé.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        // Récupérer le nouveau statut depuis le formulaire
        $newStatus = $request->request->get('status');

        // Vérifier que le statut est valide
        $validStatuses = ['backlog', 'in_progress', 'completed'];
        if (in_array($newStatus, $validStatuses) === false) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        // Mettre à jour le statut
        $userGame->setStatus($newStatus);
        $entityManager->flush();

        // Message de succès selon le statut
        $statusMessages = [
            'backlog' => '📚 Jeu ajouté au backlog',
            'in_progress' => '🎮 Jeu marqué comme en cours',
            'completed' => '✅ Jeu marqué comme terminé',
        ];

        $this->addFlash('success', $statusMessages[$newStatus]);

        // Rediriger vers la page d'où vient la requête
        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
    }

    #[Route('/toggle-favorite/{id}', name: 'app_home_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(int $id, Request $request, UserGameRepository $userGameRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer le UserGame
        $userGame = $userGameRepository->find($id);

        // Vérifier que le jeu existe
        if ($userGame === null) {
            $this->addFlash('error', 'Jeu introuvable');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        // Vérifier que c'est bien le jeu de l'utilisateur connecté
        if ($userGame->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce jeu');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        // Toggle le favori
        $isFavorite = $userGame->isFavorite();
        $userGame->setIsFavorite(!$isFavorite);

        // Sauvegarder
        $entityManager->flush();

        // Message flash
        if ($userGame->isFavorite()) {
            $this->addFlash('success', '❤️ Jeu ajouté aux favoris');
        } else {
            $this->addFlash('success', '💔 Jeu retiré des favoris');
        }

        // Rediriger vers la page d'où vient la requête
        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
    }

    #[Route('/favorites', name: 'app_favorites')]
    public function favorites(UserGameRepository $userGameRepository): Response
    {
        // Récupérer uniquement les jeux favoris de l'utilisateur
        $user = $this->getUser();
        $favoriteGames = $userGameRepository->findBy(
            ['user' => $user, 'isFavorite' => true],
            ['addedAt' => 'DESC']
        );

        // Calculer les statistiques pour le panel
        $allUserGames = $user->getUserGames();
        $stats = [
            'total' => count($allUserGames),
            'backlog' => 0,
            'in_progress' => 0,
            'completed' => 0
        ];

        foreach ($allUserGames as $userGame) {
            $status = $userGame->getStatus();
            if ($status === 'backlog') {
                $stats['backlog']++;
            } elseif ($status === 'in_progress') {
                $stats['in_progress']++;
            } elseif ($status === 'completed') {
                $stats['completed']++;
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
        // Récupérer le UserGame
        $userGame = $userGameRepository->find($id);

        // Vérifier que le jeu existe
        if ($userGame === null) {
            $this->addFlash('error', 'Jeu introuvable');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        // Vérifier que c'est bien le jeu de l'utilisateur connecté
        if ($userGame->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce jeu');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
        }

        // Récupérer le nom du jeu pour le message
        $gameName = $userGame->getGame()->getName();

        // Supprimer le UserGame
        $entityManager->remove($userGame);
        $entityManager->flush();

        // Message de succès
        $this->addFlash('success', '🗑️ "' . $gameName . '" a été retiré de votre bibliothèque');

        // Rediriger vers la page d'où vient la requête
        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_home')));
    }
}
