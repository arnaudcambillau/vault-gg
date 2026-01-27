<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\GameRepository;
use App\Repository\UserGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        GameRepository $gameRepository,
        UserGameRepository $userGameRepository
    ): Response {
        // Statistiques globales
        $totalUsers = count($userRepository->findAll());
        $totalGames = count($gameRepository->findAll());
        $totalUserGames = count($userGameRepository->findAll());
        
        // Calcul des jeux les plus populaires
        $allUserGames = $userGameRepository->findAll();
        $gameCount = [];
        
        foreach ($allUserGames as $userGame) {
            $gameName = $userGame->getGame()->getName();
            if (!isset($gameCount[$gameName])) {
                $gameCount[$gameName] = 0;
            }
            $gameCount[$gameName]++;
        }
        
        arsort($gameCount);
        $topGames = array_slice($gameCount, 0, 10, true);
        
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
        
        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalGames' => $totalGames,
            'totalUserGames' => $totalUserGames,
            'topGames' => $topGames,
            'stats' => $stats,
        ]);
    }
    
    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository, UserGameRepository $userGameRepository): Response
    {
        $users = $userRepository->findAll();
        
        // Calculer les stats pour chaque utilisateur
        $usersStats = [];
        foreach ($users as $user) {
            $userGames = $userGameRepository->findBy(['user' => $user]);
            $usersStats[] = [
                'user' => $user,
                'totalGames' => count($userGames),
                'completed' => count(array_filter($userGames, fn($ug) => $ug->getStatus() === 'completed')),
                'favorites' => count(array_filter($userGames, fn($ug) => $ug->isFavorite())),
            ];
        }
        
        // Statistiques de l'utilisateur connecté pour l'aside
        $currentUser = $this->getUser();
        $currentUserGames = $userGameRepository->findBy(['user' => $currentUser]);
        $stats = [
            'total' => count($currentUserGames),
            'backlog' => 0,
            'in_progress' => 0,
            'completed' => 0,
        ];
        
        foreach ($currentUserGames as $userGame) {
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
        
        return $this->render('admin/users.html.twig', [
            'usersStats' => $usersStats,
            'stats' => $stats,
        ]);
    }
    
    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            $this->addFlash('error', ' Utilisateur introuvable');
            return $this->redirectToRoute('app_admin_users');
        }
        
        // Ne pas pouvoir se supprimer soi-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', ' Vous ne pouvez pas vous supprimer vous-même');
            return $this->redirectToRoute('app_admin_users');
        }
        
        // Supprimer l'avatar si existe
        if ($user->getAvatar()) {
            $avatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatar();
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
        }
        
        $username = $user->getUsername();
        $em->remove($user);
        $em->flush();
        
        $this->addFlash('success', ' Utilisateur "' . $username . '" supprimé avec succès');
        return $this->redirectToRoute('app_admin_users');
    }
}