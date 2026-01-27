<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Entity\Game;
use App\Entity\UserGame;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HomeControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        // Créer un utilisateur de test
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setUsername('testuser');
        $this->user->setPassword($this->passwordHasher->hashPassword($this->user, 'password123'));
        $this->user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Nettoyer la base de données après chaque test
        $connection = $this->entityManager->getConnection();
        
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE user_game');
        $connection->executeStatement('TRUNCATE TABLE game');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('TRUNCATE TABLE messenger_messages');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function loginUser(): void
    {
        $this->client->loginUser($this->user);
    }

    public function testIndexDisplaysLibrary(): void
    {
        $this->loginUser();
        
        $crawler = $this->client->request('GET', '/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Ma Bibliothèque');
    }

    public function testIndexDisplaysUserGames(): void
    {
        $this->loginUser();
        
        // Créer un jeu
        $game = new Game();
        $game->setRawgId(123);
        $game->setName('Test Game');
        $game->setBackgroundImage('https://example.com/image.jpg');
        $game->setReleased(new \DateTime('2023-01-01'));
        $game->setRating(4.5);
        $game->setGenres(['Action', 'Adventure']);
        
        $this->entityManager->persist($game);
        
        // Créer un UserGame
        $userGame = new UserGame();
        $userGame->setUser($this->user);
        $userGame->setGame($game);
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/');
        
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Test Game', $this->client->getResponse()->getContent());
    }

    public function testIndexFiltersByBacklogStatus(): void
    {
        $this->loginUser();
        
        $game1 = new Game();
        $game1->setRawgId(124);
        $game1->setName('Backlog Game');
        $game1->setGenres(['Action']);
        
        $game2 = new Game();
        $game2->setRawgId(125);
        $game2->setName('Completed Game');
        $game2->setGenres(['Adventure']);
        
        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);
        
        $userGame1 = new UserGame();
        $userGame1->setUser($this->user);
        $userGame1->setGame($game1);
        $userGame1->setStatus('backlog');
        $userGame1->setIsFavorite(false);
        $userGame1->setAddedAt(new \DateTime());
        
        $userGame2 = new UserGame();
        $userGame2->setUser($this->user);
        $userGame2->setGame($game2);
        $userGame2->setStatus('completed');
        $userGame2->setIsFavorite(false);
        $userGame2->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame1);
        $this->entityManager->persist($userGame2);
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/?status=backlog');
        
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Backlog Game', $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('Completed Game', $this->client->getResponse()->getContent());
    }

    public function testIndexFiltersByGenre(): void
    {
        $this->loginUser();
        
        $game1 = new Game();
        $game1->setRawgId(126);
        $game1->setName('Action Game');
        $game1->setGenres(['Action']);
        
        $game2 = new Game();
        $game2->setRawgId(127);
        $game2->setName('RPG Game');
        $game2->setGenres(['RPG']);
        
        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);
        
        $userGame1 = new UserGame();
        $userGame1->setUser($this->user);
        $userGame1->setGame($game1);
        $userGame1->setStatus('backlog');
        $userGame1->setIsFavorite(false);
        $userGame1->setAddedAt(new \DateTime());
        
        $userGame2 = new UserGame();
        $userGame2->setUser($this->user);
        $userGame2->setGame($game2);
        $userGame2->setStatus('backlog');
        $userGame2->setIsFavorite(false);
        $userGame2->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame1);
        $this->entityManager->persist($userGame2);
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/?genre=Action');
        
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Action Game', $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('RPG Game', $this->client->getResponse()->getContent());
    }

    public function testChangeStatusUpdatesGameStatus(): void
    {
        $this->loginUser();
        
        $game = new Game();
        $game->setRawgId(128);
        $game->setName('Status Test Game');
        $game->setGenres(['Action']);
        
        $this->entityManager->persist($game);
        
        $userGame = new UserGame();
        $userGame->setUser($this->user);
        $userGame->setGame($game);
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $userGameId = $userGame->getId();
        
        $crawler = $this->client->request('POST', '/change-status/' . $userGameId, [
            'status' => 'in_progress'
        ]);
        
        $this->assertResponseRedirects();
        
        $updatedUserGame = $this->entityManager->getRepository(UserGame::class)->find($userGameId);
        $this->assertEquals('in_progress', $updatedUserGame->getStatus());
    }

    public function testChangeStatusWithInvalidStatus(): void
    {
        $this->loginUser();
        
        $game = new Game();
        $game->setRawgId(129);
        $game->setName('Invalid Status Game');
        $game->setGenres(['Action']);
        
        $this->entityManager->persist($game);
        
        $userGame = new UserGame();
        $userGame->setUser($this->user);
        $userGame->setGame($game);
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $userGameId = $userGame->getId();
        
        $this->client->request('POST', '/change-status/' . $userGameId, [
            'status' => 'invalid_status'
        ]);
        
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        
        $this->assertSelectorExists('[class*="bg-red-500"]');
    }

    public function testToggleFavoriteMarksGameAsFavorite(): void
    {
        $this->loginUser();
        
        $game = new Game();
        $game->setRawgId(130);
        $game->setName('Favorite Test Game');
        $game->setGenres(['Action']);
        
        $this->entityManager->persist($game);
        
        $userGame = new UserGame();
        $userGame->setUser($this->user);
        $userGame->setGame($game);
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $userGameId = $userGame->getId();
        
        $this->client->request('POST', '/toggle-favorite/' . $userGameId);
        
        $this->assertResponseRedirects();
        
        $updatedUserGame = $this->entityManager->getRepository(UserGame::class)->find($userGameId);
        $this->assertTrue($updatedUserGame->isFavorite());
    }

    public function testToggleFavoriteUnmarksGameAsFavorite(): void
    {
        $this->loginUser();
        
        $game = new Game();
        $game->setRawgId(131);
        $game->setName('Unfavorite Test Game');
        $game->setGenres(['Action']);
        
        $this->entityManager->persist($game);
        
        $userGame = new UserGame();
        $userGame->setUser($this->user);
        $userGame->setGame($game);
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(true);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $userGameId = $userGame->getId();
        
        $this->client->request('POST', '/toggle-favorite/' . $userGameId);
        
        $this->assertResponseRedirects();
        
        $updatedUserGame = $this->entityManager->getRepository(UserGame::class)->find($userGameId);
        $this->assertFalse($updatedUserGame->isFavorite());
    }

    public function testFavoritesDisplaysOnlyFavoriteGames(): void
    {
        $this->loginUser();
        
        $game1 = new Game();
        $game1->setRawgId(132);
        $game1->setName('Favorite Game');
        $game1->setGenres(['Action']);
        
        $game2 = new Game();
        $game2->setRawgId(133);
        $game2->setName('Non-Favorite Game');
        $game2->setGenres(['Action']);
        
        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);
        
        $userGame1 = new UserGame();
        $userGame1->setUser($this->user);
        $userGame1->setGame($game1);
        $userGame1->setStatus('backlog');
        $userGame1->setIsFavorite(true);
        $userGame1->setAddedAt(new \DateTime());
        
        $userGame2 = new UserGame();
        $userGame2->setUser($this->user);
        $userGame2->setGame($game2);
        $userGame2->setStatus('backlog');
        $userGame2->setIsFavorite(false);
        $userGame2->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame1);
        $this->entityManager->persist($userGame2);
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/favorites');
        
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Favorite Game', $this->client->getResponse()->getContent());
        $this->assertStringNotContainsString('Non-Favorite Game', $this->client->getResponse()->getContent());
    }

    public function testDeleteGameRemovesGameFromLibrary(): void
    {
        $this->loginUser();
        
        $game = new Game();
        $game->setRawgId(134);
        $game->setName('Delete Test Game');
        $game->setGenres(['Action']);
        
        $this->entityManager->persist($game);
        
        $userGame = new UserGame();
        $userGame->setUser($this->user);
        $userGame->setGame($game);
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $userGameId = $userGame->getId();
        
        $this->client->request('POST', '/delete-game/' . $userGameId);
        
        $this->assertResponseRedirects();
        
        $deletedUserGame = $this->entityManager->getRepository(UserGame::class)->find($userGameId);
        $this->assertNull($deletedUserGame);
    }

    public function testDeleteGameWithNonExistentGame(): void
    {
        $this->loginUser();
        
        $this->client->request('POST', '/delete-game/99999');
        
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        
        $this->assertSelectorExists('[class*="bg-red-500"]');
    }

    public function testCannotModifyOtherUserGame(): void
    {
        $this->loginUser();
        
        $otherUserEmail = 'other_' . uniqid() . '@example.com';
        $otherUser = new User();
        $otherUser->setEmail($otherUserEmail);
        $otherUser->setUsername('otheruser_' . uniqid());
        $otherUser->setPassword($this->passwordHasher->hashPassword($otherUser, 'password123'));
        $otherUser->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($otherUser);
        
        $game = new Game();
        $game->setRawgId(135);
        $game->setName('Other User Game');
        $game->setGenres(['Action']);
        
        $this->entityManager->persist($game);
        
        $userGame = new UserGame();
        $userGame->setUser($otherUser);
        $userGame->setGame($game);
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $userGameId = $userGame->getId();
        
        $this->client->request('POST', '/change-status/' . $userGameId, [
            'status' => 'completed'
        ]);
        
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        
        $this->assertSelectorExists('[class*="bg-red-500"]');
        
        $unchangedUserGame = $this->entityManager->getRepository(UserGame::class)->find($userGameId);
        $this->assertEquals('backlog', $unchangedUserGame->getStatus());
    }
}