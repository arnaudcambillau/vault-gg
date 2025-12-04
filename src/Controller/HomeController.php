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
    #[Route('/home', name: 'app_home')]
    public function index(UserGameRepository $userGameRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        // Récupérer tous les jeux de l'utilisateur
        $userGames = $userGameRepository->findBy(
            ['user' => $user],
            ['addedAt' => 'DESC'] // Tri par date d'ajout (plus récent en premier)
        );
        
        // Calculer les statistiques
        $stats = [
            'total' => count($userGames),
            'backlog' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];
        
        // Compter par statut
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

        return $this->render('home/index.html.twig', [
            'userGames' => $userGames,
            'stats' => $stats,
        ]);
    }

    #[Route('/home/change-status/{id}', name: 'app_home_change_status', methods: ['POST'])]
    public function changeStatus(int $id, Request $request, UserGameRepository $userGameRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        // Récupérer le UserGame par son ID
        $userGame = $userGameRepository->find($id);
        
        // Vérifier que le UserGame existe et appartient à l'utilisateur connecté
        if ($userGame === null || $userGame->getUser() !== $user) {
            $this->addFlash('error', 'Jeu introuvable ou non autorisé.');
            return $this->redirectToRoute('app_home');
        }
        
        // Récupérer le nouveau statut depuis le formulaire
        $newStatus = $request->request->get('status');
        
        // Vérifier que le statut est valide
        $validStatuses = ['backlog', 'in_progress', 'completed'];
        if (in_array($newStatus, $validStatuses) === false) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('app_home');
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
        
        return $this->redirectToRoute('app_home');
    }
}