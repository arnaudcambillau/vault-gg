<?php

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Game;
use App\Entity\UserGame;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests de sécurité des autorisations
 *
 * File 4/5 - Authorization Security
 * Couvre : Contrôle d'accès, prévention de l'escalade de privilèges, séparation des données
 *
 * Standards : OWASP A01:2021 (Broken Access Control), ANSSI, RGPD
 *
 * VERSION 4 FINALE : Correction merge() -> find()
 *
 * @group security
 * @group authorization
 */
class AuthorizationSecurityTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        // Nettoyer la base de données AVANT chaque test pour éviter les duplications
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE user_game');
        $connection->executeStatement('TRUNCATE TABLE game');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        
        // Nettoyer le cache de l'EntityManager
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager = null;
    }

    /**
     * Crée un utilisateur de test et le connecte
     */
    private function createAndLoginUser(string $email, string $username, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test1234!'));
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Connecter l'utilisateur
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            'email' => $email,
            'password' => 'Test1234!'
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();

        return $user;
    }

    /**
     * Vérifie que les routes protégées redirigent vers login pour utilisateurs non authentifiés
     *
     * @test
     * @covers \Symfony\Component\Security\Http\Firewall\AccessListener
     */
    #[DataProvider('protectedRoutesProvider')]
    public function testProtectedRoutesRedirectUnauthenticatedUsers(string $route): void
    {
        $this->client->request('GET', $route);

        $this->assertResponseRedirects('/login');
    }

    /**
     * Fournit les routes protégées
     */
    public static function protectedRoutesProvider(): array
    {
        return [
            ['/'],
            ['/search'],
            ['/profile'],
            ['/statistics'],
            ['/favorites']
        ];
    }

    /**
     * Vérifie que les routes admin sont bloquées pour les utilisateurs normaux
     *
     * @test
     * @covers \App\Controller\AdminController
     */
    public function testAdminRoutesBlockedForNormalUsers(): void
    {
        // Créer et connecter un utilisateur normal (ROLE_USER)
        $user = $this->createAndLoginUser(
            'normal.user@example.com',
            'normaluser',
            ['ROLE_USER']
        );

        $userId = $user->getId();

        // Tenter d'accéder au dashboard admin
        $this->client->request('GET', '/admin/dashboard');

        // Doit être bloqué (403 Forbidden)
        $this->assertResponseStatusCodeSame(403);

        // Nettoyage
        $userToDelete = $this->entityManager->find(User::class, $userId);
        if ($userToDelete) {
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();
        }
    }

    /**
     * Vérifie que les administrateurs peuvent accéder aux routes admin
     *
     * @test
     * @covers \App\Controller\AdminController
     */
    public function testAdminUsersCanAccessAdminRoutes(): void
    {
        // Créer et connecter un administrateur
        $admin = $this->createAndLoginUser(
            'admin.user@example.com',
            'adminuser',
            ['ROLE_USER', 'ROLE_ADMIN']
        );

        $adminId = $admin->getId();

        // Accéder au dashboard admin
        $this->client->request('GET', '/admin/dashboard');

        $this->assertResponseIsSuccessful();

        // Nettoyage
        $adminToDelete = $this->entityManager->find(User::class, $adminId);
        if ($adminToDelete) {
            $this->entityManager->remove($adminToDelete);
            $this->entityManager->flush();
        }
    }

    /**
     * Vérifie que les utilisateurs ne peuvent voir que leurs propres données
     *
     * @test
     * @covers \App\Controller\HomeController
     */
    public function testUsersCanOnlySeeTheirOwnData(): void
    {
        // Créer deux utilisateurs
        $user1 = $this->createAndLoginUser('user1@example.com', 'user1');
        $user1Id = $user1->getId();

        // FIX V4 : Utiliser find() au lieu de merge()
        $user1Fresh = $this->entityManager->find(User::class, $user1Id);

        // Créer un jeu pour user1
        $game = new Game();
        $game->setRawgId(12345);
        $game->setName('Test Game User1');
        $game->setBackgroundImage('https://example.com/image.jpg');
        $game->setRating(4.5);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $gameId = $game->getId();

        $userGame1 = new UserGame();
        $userGame1->setUser($user1Fresh);
        $userGame1->setGame($game);
        $userGame1->setStatus('backlog');
        $userGame1->setIsFavorite(false);
        $userGame1->setAddedAt(new \DateTime());

        $this->entityManager->persist($userGame1);
        $this->entityManager->flush();

        $userGame1Id = $userGame1->getId();

        // User1 accède à sa bibliothèque
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Test Game User1');

        // Se déconnecter
        $this->client->request('GET', '/logout');

        // Créer et connecter user2
        $user2 = $this->createAndLoginUser('user2@example.com', 'user2');
        $user2Id = $user2->getId();

        // User2 accède à sa bibliothèque (doit être vide)
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('body', 'Test Game User1');

        // Nettoyage
        $userGame1ToDelete = $this->entityManager->find(UserGame::class, $userGame1Id);
        if ($userGame1ToDelete) {
            $this->entityManager->remove($userGame1ToDelete);
        }

        $gameToDelete = $this->entityManager->find(Game::class, $gameId);
        if ($gameToDelete) {
            $this->entityManager->remove($gameToDelete);
        }

        $user1ToDelete = $this->entityManager->find(User::class, $user1Id);
        if ($user1ToDelete) {
            $this->entityManager->remove($user1ToDelete);
        }

        $user2ToDelete = $this->entityManager->find(User::class, $user2Id);
        if ($user2ToDelete) {
            $this->entityManager->remove($user2ToDelete);
        }

        $this->entityManager->flush();
    }

    /**
     * Vérifie que les utilisateurs ne peuvent modifier que leurs propres données
     *
     * @test
     * @covers \App\Controller\HomeController::changeStatus
     */
    public function testUsersCanOnlyModifyTheirOwnData(): void
    {
        // Créer user1 et un jeu
        $user1 = $this->createAndLoginUser('modify1@example.com', 'modifyuser1');
        $user1Id = $user1->getId();

        // FIX V4 : Utiliser find() au lieu de merge()
        $user1Fresh = $this->entityManager->find(User::class, $user1Id);

        $game = new Game();
        $game->setRawgId(99999);
        $game->setName('Test Game Modify');
        $game->setBackgroundImage('https://example.com/modify.jpg');
        $game->setRating(4.0);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $gameId = $game->getId();

        $userGame1 = new UserGame();
        $userGame1->setUser($user1Fresh);
        $userGame1->setGame($game);
        $userGame1->setStatus('backlog');
        $userGame1->setIsFavorite(false);
        $userGame1->setAddedAt(new \DateTime());

        $this->entityManager->persist($userGame1);
        $this->entityManager->flush();

        $userGame1Id = $userGame1->getId();

        // Se déconnecter
        $this->client->request('GET', '/logout');

        // Créer et connecter user2
        $user2 = $this->createAndLoginUser('modify2@example.com', 'modifyuser2');
        $user2Id = $user2->getId();

        // User2 tente de modifier le UserGame de User1
        $this->client->request('POST', '/change-status/' . $userGame1Id, [
            'status' => 'completed'
        ]);

        // Doit être bloqué ou redirigé
        $this->assertResponseRedirects();

        // Vérifier que le statut n'a PAS changé
        $this->entityManager->clear();
        $userGame1Check = $this->entityManager->find(UserGame::class, $userGame1Id);
        $this->assertEquals('backlog', $userGame1Check->getStatus());

        // Nettoyage
        $userGame1ToDelete = $this->entityManager->find(UserGame::class, $userGame1Id);
        if ($userGame1ToDelete) {
            $this->entityManager->remove($userGame1ToDelete);
        }

        $gameToDelete = $this->entityManager->find(Game::class, $gameId);
        if ($gameToDelete) {
            $this->entityManager->remove($gameToDelete);
        }

        $user1ToDelete = $this->entityManager->find(User::class, $user1Id);
        if ($user1ToDelete) {
            $this->entityManager->remove($user1ToDelete);
        }

        $user2ToDelete = $this->entityManager->find(User::class, $user2Id);
        if ($user2ToDelete) {
            $this->entityManager->remove($user2ToDelete);
        }

        $this->entityManager->flush();
    }

    /**
     * Vérifie que la suppression de données nécessite une vérification de propriété
     *
     * @test
     * @covers \App\Controller\HomeController::deleteGame
     */
    public function testDeleteRequiresOwnershipVerification(): void
    {
        // Créer user1 et un jeu
        $user1 = $this->createAndLoginUser('delete1@example.com', 'deleteuser1');
        $user1Id = $user1->getId();

        // FIX V4 : Utiliser find() au lieu de merge()
        $user1Fresh = $this->entityManager->find(User::class, $user1Id);

        $game = new Game();
        $game->setRawgId(88888);
        $game->setName('Test Game Delete');
        $game->setBackgroundImage('https://example.com/delete.jpg');
        $game->setRating(3.5);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $gameId = $game->getId();

        $userGame1 = new UserGame();
        $userGame1->setUser($user1Fresh);
        $userGame1->setGame($game);
        $userGame1->setStatus('in_progress');
        $userGame1->setIsFavorite(false);
        $userGame1->setAddedAt(new \DateTime());

        $this->entityManager->persist($userGame1);
        $this->entityManager->flush();

        $userGame1Id = $userGame1->getId();

        // Se déconnecter
        $this->client->request('GET', '/logout');

        // Créer et connecter user2
        $user2 = $this->createAndLoginUser('delete2@example.com', 'deleteuser2');
        $user2Id = $user2->getId();

        // User2 tente de supprimer le UserGame de User1
        $this->client->request('POST', '/delete-game/' . $userGame1Id);

        // Doit être bloqué
        $this->assertResponseRedirects();

        // Vérifier que le UserGame existe toujours
        $this->entityManager->clear();
        $userGameStillExists = $this->entityManager->getRepository(UserGame::class)->find($userGame1Id);
        $this->assertNotNull($userGameStillExists);

        // Nettoyage
        $userGame1ToDelete = $this->entityManager->find(UserGame::class, $userGame1Id);
        if ($userGame1ToDelete) {
            $this->entityManager->remove($userGame1ToDelete);
        }

        $gameToDelete = $this->entityManager->find(Game::class, $gameId);
        if ($gameToDelete) {
            $this->entityManager->remove($gameToDelete);
        }

        $user1ToDelete = $this->entityManager->find(User::class, $user1Id);
        if ($user1ToDelete) {
            $this->entityManager->remove($user1ToDelete);
        }

        $user2ToDelete = $this->entityManager->find(User::class, $user2Id);
        if ($user2ToDelete) {
            $this->entityManager->remove($user2ToDelete);
        }

        $this->entityManager->flush();
    }

    /**
     * Vérifie que les actions nécessitant une authentification sont bloquées
     *
     * @test
     * @covers \App\Controller\HomeController
     */
    public function testUnauthenticatedActionsAreBlocked(): void
    {
        // Tenter de changer le statut d'un jeu sans être connecté
        $this->client->request('POST', '/change-status/999', [
            'status' => 'completed'
        ]);

        $this->assertResponseRedirects('/login');

        // Tenter de supprimer un jeu sans être connecté
        $this->client->request('POST', '/delete-game/999');

        $this->assertResponseRedirects('/login');
    }

    /**
     * Vérifie que les utilisateurs admin ne peuvent pas supprimer leur propre compte
     *
     * @test
     * @covers \App\Controller\AdminController::deleteUser
     */
    public function testAdminCannotDeleteOwnAccount(): void
    {
        // Créer et connecter un administrateur
        $admin = $this->createAndLoginUser(
            'admin.self@example.com',
            'adminselfdelete',
            ['ROLE_USER', 'ROLE_ADMIN']
        );

        $adminId = $admin->getId();

        // Tenter de supprimer son propre compte
        $this->client->request('POST', '/admin/users/' . $adminId . '/delete');

        $this->assertResponseRedirects('/admin/users');

        $this->client->followRedirect();

        // Vérifier que le compte existe toujours
        $this->entityManager->clear();
        $adminStillExists = $this->entityManager->getRepository(User::class)->find($adminId);
        $this->assertNotNull($adminStillExists);

        // Nettoyage
        $adminToDelete = $this->entityManager->find(User::class, $adminId);
        if ($adminToDelete) {
            $this->entityManager->remove($adminToDelete);
            $this->entityManager->flush();
        }
    }

    /**
     * Vérifie le contrôle d'accès basé sur les rôles (RBAC)
     *
     * @test
     * @covers \Symfony\Component\Security\Core\Authorization\AccessDecisionManager
     */
    public function testRoleBasedAccessControl(): void
    {
        // Créer un utilisateur normal
        $user = $this->createAndLoginUser('rbac@example.com', 'rbacuser', ['ROLE_USER']);
        $userId = $user->getId();

        // Vérifier l'accès aux routes utilisateur (doit fonctionner)
        $this->client->request('GET', '/profile');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        // Vérifier le refus d'accès aux routes admin
        $this->client->request('GET', '/admin/dashboard');
        $this->assertResponseStatusCodeSame(403);

        // Nettoyage
        $userToDelete = $this->entityManager->find(User::class, $userId);
        if ($userToDelete) {
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();
        }
    }

    /**
     * Vérifie que les tentatives d'escalade de privilèges sont bloquées
     *
     * @test
     * @covers \App\Controller\AdminController
     */
    public function testPrivilegeEscalationIsBlocked(): void
    {
        // Créer un utilisateur normal
        $user = $this->createAndLoginUser('escalade@example.com', 'escaladeuser', ['ROLE_USER']);
        $userId = $user->getId();

        // Tenter d'accéder directement à des routes admin avec différentes méthodes
        $adminRoutes = [
            '/admin/dashboard',
            '/admin/users'
        ];

        foreach ($adminRoutes as $route) {
            $this->client->request('GET', $route);
            $this->assertResponseStatusCodeSame(403);
        }

        // Nettoyage
        $userToDelete = $this->entityManager->find(User::class, $userId);
        if ($userToDelete) {
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();
        }
    }
}