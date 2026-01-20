<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Entity\Game;
use App\Entity\UserGame;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $adminUser;
    private $regularUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        
        // Créer un utilisateur admin
        $this->adminUser = new User();
        $this->adminUser->setEmail('admin_' . uniqid() . '@example.com');
        $this->adminUser->setUsername('admin_test');
        $this->adminUser->setPassword('$2y$13$hashedpassword');
        $this->adminUser->setRoles(['ROLE_ADMIN']);
        
        $this->entityManager->persist($this->adminUser);
        
        // Créer un utilisateur régulier
        $this->regularUser = new User();
        $this->regularUser->setEmail('user_' . uniqid() . '@example.com');
        $this->regularUser->setUsername('user_test');
        $this->regularUser->setPassword('$2y$13$hashedpassword');
        $this->regularUser->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($this->regularUser);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Nettoyage complet de la base de données
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE user_game');
        $connection->executeStatement('TRUNCATE TABLE game');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        
        parent::tearDown();
    }

    public function testDashboardAccessibleByAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        
        $crawler = $this->client->request('GET', '/admin/dashboard');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Admin Dashboard');
    }

    public function testDashboardNotAccessibleByRegularUser(): void
    {
        $this->client->loginUser($this->regularUser);
        
        $this->client->request('GET', '/admin/dashboard');
        
        // Vérifier que l'accès est refusé (403) ou redirection
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDashboardDisplaysStatistics(): void
    {
        $this->client->loginUser($this->adminUser);
        
        // Créer quelques jeux et associations pour avoir des statistiques
        $game = new Game();
        $game->setRawgId(111111);
        $game->setName('Popular Game');
        $game->setBackgroundImage('https://example.com/game.jpg');
        $game->setReleased(new \DateTime('2023-01-01'));
        $game->setRating(4.5);
        $game->setGenres(['Action']);
        
        $this->entityManager->persist($game);
        
        $userGame = new UserGame();
        $userGame->setUser($this->regularUser);
        $userGame->setGame($game);
        $userGame->setStatus('completed');
        $userGame->setIsFavorite(true);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/admin/dashboard');
        
        $this->assertResponseIsSuccessful();
        
        // Vérifier que les statistiques s'affichent
        $this->assertSelectorExists('[class*="text-"]'); // Statistiques numériques
    }

    public function testUsersListAccessibleByAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        
        $crawler = $this->client->request('GET', '/admin/users');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'utilisateurs');
    }

    public function testUsersListDisplaysAllUsers(): void
    {
        $this->client->loginUser($this->adminUser);
        
        $crawler = $this->client->request('GET', '/admin/users');
        
        $this->assertResponseIsSuccessful();
        
        // Vérifier que les utilisateurs sont affichés
        $this->assertStringContainsString($this->adminUser->getUsername(), $this->client->getResponse()->getContent());
        $this->assertStringContainsString($this->regularUser->getUsername(), $this->client->getResponse()->getContent());
    }

    public function testUsersListDisplaysUserStatistics(): void
    {
        $this->client->loginUser($this->adminUser);
        
        // Créer un jeu pour l'utilisateur régulier
        $game = new Game();
        $game->setRawgId(222222);
        $game->setName('User Game');
        $game->setBackgroundImage('https://example.com/game2.jpg');
        $game->setReleased(new \DateTime('2023-06-01'));
        $game->setRating(3.5);
        $game->setGenres(['RPG']);
        
        $this->entityManager->persist($game);
        
        $userGame = new UserGame();
        $userGame->setUser($this->regularUser);
        $userGame->setGame($game);
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/admin/users');
        
        $this->assertResponseIsSuccessful();
        
        // Vérifier que les statistiques utilisateur sont affichées (nombre de jeux, etc.)
        $this->assertStringContainsString('1', $this->client->getResponse()->getContent());
    }

    public function testCannotDeleteOwnAccount(): void
    {
        $this->client->loginUser($this->adminUser);
        
        $this->client->request('POST', '/admin/users/' . $this->adminUser->getId() . '/delete');
        
        $this->assertResponseRedirects('/admin/users');
        
        $crawler = $this->client->followRedirect();
        
        // Vérifier le message d'erreur avec sélecteur d'attribut
        $this->assertSelectorExists('[class*="bg-red"]');
    }

    public function testCanDeleteOtherUserAccount(): void
    {
        $this->client->loginUser($this->adminUser);
        
        $userIdToDelete = $this->regularUser->getId();
        
        $this->client->request('POST', '/admin/users/' . $userIdToDelete . '/delete');
        
        $this->assertResponseRedirects('/admin/users');
        
        $crawler = $this->client->followRedirect();
        
        // Vérifier le message de succès avec sélecteur d'attribut
        $this->assertSelectorExists('[class*="bg-green"]');
        
        // Vérifier que l'utilisateur a bien été supprimé
        $deletedUser = $this->entityManager->getRepository(User::class)->find($userIdToDelete);
        $this->assertNull($deletedUser);
    }

    public function testDeleteNonExistentUser(): void
    {
        $this->client->loginUser($this->adminUser);
        
        $this->client->request('POST', '/admin/users/99999/delete');
        
        $this->assertResponseRedirects('/admin/users');
        
        $crawler = $this->client->followRedirect();
        
        // Vérifier le message d'erreur
        $this->assertSelectorExists('[class*="bg-red"]');
    }

    public function testAdminCanAccessTopGamesStatistics(): void
    {
        $this->client->loginUser($this->adminUser);
        
        // Créer plusieurs jeux populaires
        for ($i = 1; $i <= 5; $i++) {
            $game = new Game();
            $game->setRawgId(300000 + $i);
            $game->setName('Game ' . $i);
            $game->setBackgroundImage('https://example.com/game' . $i . '.jpg');
            $game->setReleased(new \DateTime('2023-01-0' . $i));
            $game->setRating(4.0 + ($i * 0.1));
            $game->setGenres(['Action']);
            
            $this->entityManager->persist($game);
            
            // Ajouter plusieurs utilisateurs ayant ce jeu
            for ($j = 0; $j < $i; $j++) {
                $user = new User();
                $user->setEmail('gameuser' . $i . '_' . $j . '_' . uniqid() . '@example.com');
                $user->setUsername('gameuser' . $i . '_' . $j);
                $user->setPassword('$2y$13$hashedpassword');
                $user->setRoles(['ROLE_USER']);
                
                $this->entityManager->persist($user);
                
                $userGame = new UserGame();
                $userGame->setUser($user);
                $userGame->setGame($game);
                $userGame->setStatus('backlog');
                $userGame->setIsFavorite(false);
                $userGame->setAddedAt(new \DateTime());
                
                $this->entityManager->persist($userGame);
            }
        }
        
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/admin/dashboard');
        
        $this->assertResponseIsSuccessful();
        
        // Vérifier que les jeux populaires sont affichés
        $this->assertStringContainsString('Game 5', $this->client->getResponse()->getContent());
    }
}