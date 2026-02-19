<?php

namespace App\Controller;

use App\Document\Review;
use App\Entity\User;
use App\Repository\ReviewRepository;
use App\Service\RawgApiService;
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
        $game          = $rawgApiService->getGameDetails($rawgId);
        $reviews       = $reviewRepository->findByGameId($rawgId);
        $averageRating = $reviewRepository->getAverageRatingForGame($rawgId);
        $totalReviews  = $reviewRepository->countByGameId($rawgId);

        return $this->render('review/game_reviews.html.twig', [
            'game'          => $game,
            'reviews'       => $reviews,
            'rawgId'        => $rawgId,
            'averageRating' => $averageRating,
            'totalReviews'  => $totalReviews,
        ]);
    }

    /**
     * Ajouter un avis
     */
    #[Route('/review/add', name: 'app_review_add', methods: ['POST'])]
    public function add(Request $request, ReviewRepository $reviewRepository, RawgApiService $rawgApiService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour laisser un avis');
            return $this->redirectToRoute('app_login');
        }

        $rawgId  = (int) $request->request->get('rawgId');
        $rating  = (int) $request->request->get('rating');
        $title   = trim($request->request->get('title'));
        $comment = trim($request->request->get('comment'));

        if (!$rawgId || !$rating || !$title || !$comment) {
            $this->addFlash('error', 'Tous les champs sont requis');
            return $this->redirectToRoute('app_game_reviews', ['rawgId' => $rawgId]);
        }

        if ($rating < 1 || $rating > 5) {
            $this->addFlash('error', 'La note doit être entre 1 et 5');
            return $this->redirectToRoute('app_game_reviews', ['rawgId' => $rawgId]);
        }

        $gameDetails = $rawgApiService->getGameDetails($rawgId);
        $gameName    = $gameDetails['name'] ?? 'Jeu inconnu';

        $review = new Review();
        $review->setUserId($user->getId());
        $review->setUsername($user->getUsername());
        $review->setUserAvatar(null); // Avatar non stocké pour la sécurité
        $review->setGameId($rawgId);
        $review->setGameName($gameName);
        $review->setRating($rating);
        $review->setTitle($title);
        $review->setContent($comment);
        $review->setCreatedAt(new \DateTime());
        $review->setUpdatedAt(new \DateTime());
        $review->setHelpfulCount(0);
        $review->setHelpfulUsers([]);

        $reviewRepository->save($review);

        $this->addFlash('success', 'Votre avis a été publié avec succès !');
        return $this->redirectToRoute('app_game_reviews', ['rawgId' => $rawgId]);
    }

    /**
     * Supprimer un avis (auteur OU admin)
     */
    #[Route('/review/{id}/delete', name: 'app_review_delete', methods: ['POST'])]
    public function delete(string $id, ReviewRepository $reviewRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté');
            return $this->redirectToRoute('app_login');
        }

        $review = $reviewRepository->findOneById($id);

        if (!$review) {
            $this->addFlash('error', 'Avis introuvable');
            return $this->redirectToRoute('app_home');
        }

        $isAuthor = $review->getUserId() === $user->getId();
        $isAdmin  = $this->isGranted('ROLE_ADMIN');

        // Auteur OU admin peuvent supprimer
        if (!$isAuthor && !$isAdmin) {
            $this->addFlash('error', 'Vous n\'avez pas la permission de supprimer cet avis');
            return $this->redirectToRoute('app_game_reviews', ['rawgId' => $review->getGameId()]);
        }

        $rawgId = $review->getGameId();
        $reviewRepository->delete($id);

        $this->addFlash('success', 'Avis supprimé avec succès');
        return $this->redirectToRoute('app_game_reviews', ['rawgId' => $rawgId]);
    }

    /**
     * Toggle like/unlike sur un avis
     */
    #[Route('/review/{id}/helpful', name: 'app_review_helpful', methods: ['POST'])]
    public function helpful(string $id, ReviewRepository $reviewRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté');
            return $this->redirectToRoute('app_login');
        }

        $review = $reviewRepository->findOneById($id);

        if (!$review) {
            $this->addFlash('error', 'Avis introuvable');
            return $this->redirectToRoute('app_home');
        }

        // Toggle — like si pas liké, unlike si déjà liké
        if ($review->hasUserLiked($user->getId())) {
            $review->removeHelpfulUser($user->getId());
        } else {
            $review->addHelpfulUser($user->getId());
        }

        $reviewRepository->save($review);

        return $this->redirectToRoute('app_game_reviews', ['rawgId' => $review->getGameId()]);
    }
}