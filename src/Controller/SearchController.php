<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\UserGame;
use App\Repository\UserGameRepository;
use App\Service\RawgApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(Request $request, RawgApiService $rawgApiService, UserGameRepository $userGameRepository): Response
    {
        // Récupérer la recherche depuis le formulaire
        $query = $request->query->get('q', '');
        
        // Résultats vides par défaut
        $results = [];
        $error = null;
        
        // Si une recherche est effectuée
        if ($query !== '') {
            try {
                $data = $rawgApiService->searchGames($query);
                $results = $data['results'] ?? [];
            } catch (\Exception $e) {
                $error = 'Erreur lors de la recherche des jeux. Veuillez réessayer.';
            }
        }

        // Statistiques de l'utilisateur connecté pour l'aside
        $user = $this->getUser();
        $userGames = $userGameRepository->findBy(['user' => $user]);
        $stats = [
            'total' => count($userGames),
            'backlog' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];
        
        foreach ($userGames as $userGame) {
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

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results,
            'stats' => $stats,
            'error' => $error,
        ]);
    }

    #[Route('/search/add/{rawgId}', name: 'app_search_add', methods: ['POST'])]
    public function addGame(int $rawgId, RawgApiService $rawgApiService, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        // Vérifier si l'utilisateur est connecté
        if ($user === null) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter un jeu.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les détails du jeu depuis RAWG
        $gameData = $rawgApiService->getGameDetails($rawgId);
        
        // Vérifier si le jeu existe déjà dans notre base de données
        $gameRepository = $entityManager->getRepository(Game::class);
        $game = $gameRepository->findOneBy(['rawgId' => $rawgId]);
        
        // Si le jeu n'existe pas, on le crée
        if ($game === null) {
            $game = new Game();
            $game->setRawgId($rawgId);
            $game->setName($gameData['name']);
            $game->setBackgroundImage($gameData['background_image']);
            
            // Date de sortie
            if (isset($gameData['released']) && $gameData['released'] !== null) {
                $releasedDate = new \DateTime($gameData['released']);
                $game->setReleased($releasedDate);
            }
            
            // Note
            if (isset($gameData['rating']) && $gameData['rating'] !== null) {
                $game->setRating($gameData['rating']);
            }
            
            // Genres
            if (isset($gameData['genres']) && is_array($gameData['genres'])) {
                $genresArray = [];
                foreach ($gameData['genres'] as $genre) {
                    $genresArray[] = $genre['name'];
                }
                $game->setGenres($genresArray);
            }
            
            // Persister le jeu
            $entityManager->persist($game);
        }
        
        // Vérifier si l'utilisateur a déjà ce jeu dans sa bibliothèque
        $userGameRepository = $entityManager->getRepository(UserGame::class);
        $existingUserGame = $userGameRepository->findOneBy([
            'user' => $user,
            'game' => $game
        ]);
        
        // Si l'utilisateur a déjà ce jeu
        if ($existingUserGame !== null) {
            $this->addFlash('warning', 'Ce jeu est déjà dans votre bibliothèque !');
            return $this->redirectToRoute('app_search');
        }
        
        // Créer l'entrée UserGame (lien entre user et game)
        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);
        $userGame->setStatus('backlog'); // Statut par défaut
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        // Persister l'entrée UserGame
        $entityManager->persist($userGame);
        
        // Enregistrer en base de données
        $entityManager->flush();
        
        // Message de succès
        $this->addFlash('success', $gameData['name'] . ' a été ajouté à votre bibliothèque ! 🎉');
        
        // Rediriger vers la page de recherche
        return $this->redirectToRoute('app_search');
    }
}