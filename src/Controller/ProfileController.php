<?php

namespace App\Controller;

use App\Form\AvatarFormType;
use App\Repository\UserGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
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

        // Taux de complétion
        $completionRate = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;

        // Derniers jeux ajoutés
        $recentGames = $userGameRepository->findBy(
            ['user' => $user],
            ['addedAt' => 'DESC'],
            5
        );

        // Formulaire d'upload d'avatar
        $avatarForm = $this->createForm(AvatarFormType::class);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'completionRate' => $completionRate,
            'recentGames' => $recentGames,
            'avatarForm' => $avatarForm,
        ]);
    }
    
    #[Route('/profile/upload-avatar', name: 'app_profile_upload_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        $form = $this->createForm(AvatarFormType::class);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $avatarFile = $form->get('avatar')->getData();
    
            if ($avatarFile) {
                $originalName = $avatarFile->getClientOriginalName();
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
                if (!in_array($extension, $allowedExtensions)) {
                    $this->addFlash('error', 'Format non autorisé. Utilisez : JPG, PNG, GIF, WEBP');
                    return $this->redirectToRoute('app_profile');
                }
    
                if ($avatarFile->getSize() > 2097152) {
                    $this->addFlash('error', 'L\'image ne doit pas dépasser 2 Mo');
                    return $this->redirectToRoute('app_profile');
                }
    
                $newFilename = uniqid() . '.' . $extension;
    
                try {
                    $avatarFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                    return $this->redirectToRoute('app_profile');
                }
    
                // Supprimer l'ancien avatar uniquement s'il existe et n'est pas vide
                if ($user->getAvatar()) {
                    $oldAvatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getAvatar();
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
    
                // Sauvegarder le nouveau nom de fichier
                $user->setAvatar($newFilename);
                $entityManager->flush();
    
                $this->addFlash('success', 'Photo de profil mise à jour avec succès !');
            }
        } else {
            $this->addFlash('error', 'Erreur : Veuillez choisir une image valide');
        }
    
        return $this->redirectToRoute('app_profile');
    }


    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Supprimer l'utilisateur
        $em->remove($user);
        $em->flush();
    
        // Déconnecter l'utilisateur
        $this->container->get('security.token_storage')->setToken(null);
        $request->getSession()->invalidate();
    
        $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
    
        return $this->redirectToRoute('app_login');
    }

}