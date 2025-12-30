<?php

namespace App\Controller;

use App\Document\Review;
use App\Repository\GameRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    #[Route('/game/{id}/reviews', name: 'app_game_reviews')]
    public function gameReviews(
        int $id,
        GameRepository $gameRepository,
        ReviewRepository $reviewRepository
    ): Response {
        $game = $gameRepository->find($id);

        if (!$game) {
            throw $this->createNotFoundException('Jeu introuvable');
        }

        $reviews = $reviewRepository->findByGameId($id);
        $averageRating = $reviewRepository->getAverageRatingForGame($id);
        $totalReviews = $reviewRepository->countByGameId($id);

        return $this->render('review/game_reviews.html.twig', [
            'game' => $game,
            'reviews' => $reviews,
            'averageRating' => $averageRating,
            'totalReviews' => $totalReviews,
        ]);
    }

    #[Route('/game/{id}/review/add', name: 'app_review_add', methods: ['GET', 'POST'])]
    public function addReview(
        int $id,
        Request $request,
        GameRepository $gameRepository,
        ReviewRepository $reviewRepository
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour laisser un avis');
            return $this->redirectToRoute('app_login');
        }

        $game = $gameRepository->find($id);

        if (!$game) {
            throw $this->createNotFoundException('Jeu introuvable');
        }

        if ($request->isMethod('POST')) {
            $rating = (int) $request->request->get('rating');
            $title = $request->request->get('title');
            $content = $request->request->get('content');
            $isRecommended = $request->request->get('isRecommended') === 'on';

            if ($rating < 1 || $rating > 5) {
                $this->addFlash('error', 'La note doit être entre 1 et 5');
                return $this->redirectToRoute('app_review_add', ['id' => $id]);
            }

            if (empty($title) || empty($content)) {
                $this->addFlash('error', 'Le titre et le contenu sont obligatoires');
                return $this->redirectToRoute('app_review_add', ['id' => $id]);
            }

            $review = new Review();
            $review->setUserId($user->getId());
            $review->setUsername($user->getUsername());
            $review->setUserAvatar($user->getAvatar());
            $review->setGameId($game->getId());
            $review->setGameName($game->getName());
            $review->setRating($rating);
            $review->setTitle($title);
            $review->setContent($content);
            $review->setIsRecommended($isRecommended);

            $reviewRepository->save($review);

            $this->addFlash('success', '✅ Votre avis a été publié avec succès !');
            return $this->redirectToRoute('app_game_reviews', ['id' => $id]);
        }

        return $this->render('review/add_review.html.twig', [
            'game' => $game,
        ]);
    }

    #[Route('/review/{id}/helpful', name: 'app_review_helpful', methods: ['POST'])]
    public function markHelpful(
        string $id,
        ReviewRepository $reviewRepository
    ): Response {
        $review = $reviewRepository->findOneById($id);

        if (!$review) {
            throw $this->createNotFoundException('Avis introuvable');
        }

        $review->incrementHelpfulCount();
        $reviewRepository->save($review);

        $this->addFlash('success', '👍 Merci pour votre retour !');
        return $this->redirect($this->generateUrl('app_game_reviews', ['id' => $review->getGameId()]));
    }

    #[Route('/profile/reviews', name: 'app_profile_reviews')]
    public function myReviews(ReviewRepository $reviewRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reviews = $reviewRepository->findByUserId($user->getId());

        return $this->render('review/my_reviews.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/review/{id}/delete', name: 'app_review_delete', methods: ['POST'])]
    public function deleteReview(
        string $id,
        ReviewRepository $reviewRepository
    ): Response {
        $user = $this->getUser();
        $review = $reviewRepository->findOneById($id);

        if (!$review) {
            throw $this->createNotFoundException('Avis introuvable');
        }

        if ($review->getUserId() !== $user->getId()) {
            $this->addFlash('error', '❌ Vous ne pouvez pas supprimer cet avis');
            return $this->redirectToRoute('app_profile_reviews');
        }

        $reviewRepository->delete($review);

        $this->addFlash('success', '🗑️ Votre avis a été supprimé');
        return $this->redirectToRoute('app_profile_reviews');
    }
}