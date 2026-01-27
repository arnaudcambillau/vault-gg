<?php

namespace App\Controller;

use App\Document\Review;
use App\Repository\ReviewRepository;
use App\Service\RawgApiService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    /**
     * Afficher les avis d'un jeu
     */
    #[Route('/game/{rawgId}/reviews', name: 'app_game_reviews', methods: ['GET'])]
    public function gameReviews(int $rawgId, ReviewRepository $reviewRepository, RawgApiService $rawgApiService): Response
    {
        // Récupérer les infos du jeu depuis l'API RAWG
        $game = $rawgApiService->getGameDetails($rawgId);
        
        // Récupérer les avis depuis MongoDB
        $reviews = $reviewRepository->findByGameId($rawgId);
        $averageRating = $reviewRepository->getAverageRatingForGame($rawgId);
        $totalReviews = $reviewRepository->countByGameId($rawgId);

        return $this->render('review/game_reviews.html.twig', [
            'game' => $game,
            'reviews' => $reviews,
            'rawgId' => $rawgId,
            'averageRating' => $averageRating,
            'totalReviews' => $totalReviews,
        ]);
    }

    /**
     * Ajouter un avis
     */
    #[Route('/review/add', name: 'app_review_add', methods: ['POST'])]
    public function add(Request $request, ReviewRepository $reviewRepository, RawgApiService $rawgApiService): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', ' Vous devez être connecté pour laisser un avis');
            return $this->redirectToRoute('app_login');
        }

        // RÉCUPÉRATION DES DONNÉES DU FORMULAIRE
        $rawgId = (int) $request->request->get('rawgId');
        $rating = (int) $request->request->get('rating');
        $title = trim($request->request->get('title'));
        $comment = trim($request->request->get('comment'));  

        // VALIDATION SIMPLE (ne vérifie que les champs qui existent vraiment)
        if (!$rawgId || !$rating || !$title || !$comment) {
            $this->addFlash('error', ' Tous les champs sont requis');
            return $this->redirectToRoute('app_game_reviews', ['rawgId' => $rawgId]);
        }

        if ($rating < 1 || $rating > 5) {
            $this->addFlash('error', ' La note doit être entre 1 et 5');
            return $this->redirectToRoute('app_game_reviews', ['rawgId' => $rawgId]);
        }

        // RÉCUPÉRER LE NOM DU JEU DEPUIS L'API RAWG
        $gameDetails = $rawgApiService->getGameDetails($rawgId);
        $gameName = $gameDetails['name'] ?? 'Jeu inconnu';

        // CRÉER L'AVIS
        $review = new Review();
        $review->setUserId($user->getId());
        $review->setUsername($user->getUsername());
        $review->setUserAvatar($user->getAvatar());
        $review->setGameId($rawgId);
        $review->setGameName($gameName);
        $review->setRating($rating);
        $review->setTitle($title);
        $review->setContent($comment);  
        $review->setCreatedAt(new \DateTime());
        $review->setUpdatedAt(new \DateTime());
        $review->setHelpfulCount(0);

        $reviewRepository->save($review);

        $this->addFlash('success', ' Votre avis a été publié avec succès !');
        return $this->redirectToRoute('app_game_reviews', ['rawgId' => $rawgId]);
    }

    /**
     * Supprimer un avis
     */
    #[Route('/review/{id}/delete', name: 'app_review_delete', methods: ['POST'])]
    public function delete(string $id, ReviewRepository $reviewRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', ' Vous devez être connecté');
            return $this->redirectToRoute('app_login');
        }

        $review = $reviewRepository->findOneById($id);

        if (!$review) {
            $this->addFlash('error', ' Avis introuvable');
            return $this->redirectToRoute('app_home');
        }

        // Vérifier que c'est l'auteur
        if ($review->getUserId() !== $user->getId()) {
            $this->addFlash('error', ' Vous ne pouvez supprimer que vos propres avis');
            return $this->redirectToRoute('app_game_reviews', ['rawgId' => $review->getGameId()]);
        }

        $rawgId = $review->getGameId();
        $reviewRepository->delete($id);

        $this->addFlash('success', ' Votre avis a été supprimé');
        return $this->redirectToRoute('app_game_reviews', ['rawgId' => $rawgId]);
    }

    /**
     * Marquer un avis comme utile
     */
    #[Route('/review/{id}/helpful', name: 'app_review_helpful', methods: ['POST'])]
    public function helpful(string $id, ReviewRepository $reviewRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', ' Vous devez être connecté');
            return $this->redirectToRoute('app_login');
        }

        $review = $reviewRepository->findOneById($id);

        if (!$review) {
            $this->addFlash('error', ' Avis introuvable');
            return $this->redirectToRoute('app_home');
        }

        // Incrémenter le compteur
        $review->setHelpfulCount($review->getHelpfulCount() + 1);
        $reviewRepository->save($review);

        $this->addFlash('success', '👍 Merci pour votre retour !');
        return $this->redirectToRoute('app_game_reviews', ['rawgId' => $review->getGameId()]);
    }
}
