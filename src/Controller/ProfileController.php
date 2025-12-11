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
        $form = $this->createForm(AvatarFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile) {
                // Extraire l'extension
                $originalName = $avatarFile->getClientOriginalName();
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                
                // Valider l'extension
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($extension, $allowedExtensions)) {
                    $this->addFlash('error', '❌ Format non autorisé. Utilisez : JPG, PNG, GIF, WEBP');
                    return $this->redirectToRoute('app_profile');
                }
                
                // Valider la taille (2 Mo max)
                if ($avatarFile->getSize() > 2097152) {
                    $this->addFlash('error', '❌ L\'image ne doit pas dépasser 2 Mo');
                    return $this->redirectToRoute('app_profile');
                }
                
                $newFilename = uniqid() . '.' . $extension;

                // Déplacer le fichier vers le dossier uploads/avatars
                try {
                    $avatarFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', '❌ Erreur lors de l\'upload de l\'image');
                    return $this->redirectToRoute('app_profile');
                }

                // Supprimer l'ancien avatar s'il existe
                $user = $this->getUser();
                $oldAvatar = $user->getAvatar();
                if ($oldAvatar) {
                    $oldAvatarPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $oldAvatar;
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }

                // Sauvegarder le nouveau nom de fichier
                $user->setAvatar($newFilename);
                $entityManager->flush();

                $this->addFlash('success', '✅ Photo de profil mise à jour avec succès !');
            }
        } else {
            $this->addFlash('error', '❌ Erreur : Veuillez choisir une image valide');
        }

        return $this->redirectToRoute('app_profile');
    }
}