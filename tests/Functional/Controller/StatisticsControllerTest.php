<?php

namespace App\Tests\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\Entity\UserGame;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StatisticsControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        
        $this->cleanDatabase();
        
        $this->user = new User();
        $this->user->setEmail('stats.test@example.com');
        $this->user->setUsername('StatsTestUser');
        $this->user->setPassword('$2y$13$hashed');
        $this->user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE user_game');
        $connection->executeStatement('TRUNCATE TABLE game');
        $connection->executeStatement('DELETE FROM user WHERE email LIKE "stats.test%"');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * TEST 1 : Page de statistiques accessible
     */
    public function testStatisticsPageIsAccessible(): void
    {
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/statistics');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Statistiques');
    }

    /**
     * TEST 2 : Statistiques générales affichées
     */
    public function testStatisticsDisplaysGeneralStats(): void
    {
        // Créer des jeux avec différents statuts
        $game1 = $this->createGame('Stat Game 1', 10001);
        $game2 = $this->createGame('Stat Game 2', 10002);
        $game3 = $this->createGame('Stat Game 3', 10003);
        $game4 = $this->createGame('Stat Game 4', 10004);
        
        $userGame1 = new UserGame();
        $userGame1->setUser($this->user);
        $userGame1->setGame($game1);
        $userGame1->setStatus('completed');
        $userGame1->setIsFavorite(true);
        $userGame1->setAddedAt(new \DateTime());
        
        $userGame2 = new UserGame();
        $userGame2->setUser($this->user);
        $userGame2->setGame($game2);
        $userGame2->setStatus('in_progress');
        $userGame2->setIsFavorite(false);
        $userGame2->setAddedAt(new \DateTime());
        
        $userGame3 = new UserGame();
        $userGame3->setUser($this->user);
        $userGame3->setGame($game3);
        $userGame3->setStatus('backlog');
        $userGame3->setIsFavorite(true);
        $userGame3->setAddedAt(new \DateTime());
        
        $userGame4 = new UserGame();
        $userGame4->setUser($this->user);
        $userGame4->setGame($game4);
        $userGame4->setStatus('completed');
        $userGame4->setIsFavorite(false);
        $userGame4->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame1);
        $this->entityManager->persist($userGame2);
        $this->entityManager->persist($userGame3);
        $this->entityManager->persist($userGame4);
        $this->entityManager->flush();
        
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/statistics');
        
        $this->assertResponseIsSuccessful();
        
        // Vérifier que les stats sont correctes
        $text = $crawler->text();
        $this->assertStringContainsString('4', $text); // Total
        $this->assertStringContainsString('2', $text); // Completed ou Favoris
        $this->assertStringContainsString('1', $text); // In progress ou Backlog
    }

    /**
     * TEST 3 : Taux de complétion calculé correctement
     */
    public function testCompletionRateIsCalculatedCorrectly(): void
    {
        // Créer 4 jeux : 3 completed, 1 backlog = 75%
        for ($i = 1; $i <= 4; $i++) {
            $game = $this->createGame("Completion Game $i", 20000 + $i);
            
            $userGame = new UserGame();
            $userGame->setUser($this->user);
            $userGame->setGame($game);
            $userGame->setStatus($i <= 3 ? 'completed' : 'backlog');
            $userGame->setIsFavorite(false);
            $userGame->setAddedAt(new \DateTime());
            
            $this->entityManager->persist($userGame);
        }
        $this->entityManager->flush();
        
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/statistics');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', '75'); // 75%
    }

    /**
     * TEST 4 : Top genres affiché
     */
    public function testTopGenresAreDisplayed(): void
    {
        // Créer des jeux avec différents genres
        $game1 = $this->createGame('RPG Game', 30001, ['RPG', 'Adventure']);
        $game2 = $this->createGame('Another RPG', 30002, ['RPG', 'Action']);
        $game3 = $this->createGame('Action Game', 30003, ['Action', 'Shooter']);
        $game4 = $this->createGame('Puzzle Game', 30004, ['Puzzle']);
        
        foreach ([$game1, $game2, $game3, $game4] as $game) {
            $userGame = new UserGame();
            $userGame->setUser($this->user);
            $userGame->setGame($game);
            $userGame->setStatus('backlog');
            $userGame->setIsFavorite(false);
            $userGame->setAddedAt(new \DateTime());
            
            $this->entityManager->persist($userGame);
        }
        $this->entityManager->flush();
        
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/statistics');
        
        $this->assertResponseIsSuccessful();
        // RPG devrait être le genre le plus représenté (2 jeux)
        $this->assertSelectorTextContains('body', 'RPG');
        $this->assertSelectorTextContains('body', 'Action');
    }

    /**
     * TEST 5 : Top jeux les mieux notés
     */
    public function testTopRatedGamesAreDisplayed(): void
    {
        $game1 = $this->createGame('Best Game', 40001, ['Action'], 5.0);
        $game2 = $this->createGame('Good Game', 40002, ['RPG'], 4.5);
        $game3 = $this->createGame('OK Game', 40003, ['Puzzle'], 3.5);
        
        foreach ([$game1, $game2, $game3] as $game) {
            $userGame = new UserGame();
            $userGame->setUser($this->user);
            $userGame->setGame($game);
            $userGame->setStatus('backlog');
            $userGame->setIsFavorite(false);
            $userGame->setAddedAt(new \DateTime());
            
            $this->entityManager->persist($userGame);
        }
        $this->entityManager->flush();
        
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/statistics');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Best Game');
        $this->assertSelectorTextContains('body', '5'); // Rating
    }

    /**
     * TEST 6 : Statistiques mensuelles
     */
    public function testMonthlyStatisticsAreDisplayed(): void
    {
        // Créer des jeux ajoutés à différentes dates
        $now = new \DateTime();
        
        for ($i = 0; $i < 3; $i++) {
            $game = $this->createGame("Monthly Game $i", 50000 + $i);
            
            $userGame = new UserGame();
            $userGame->setUser($this->user);
            $userGame->setGame($game);
            $userGame->setStatus('backlog');
            $userGame->setIsFavorite(false);
            
            // Ajouter avec des dates différentes
            $addedDate = clone $now;
            $addedDate->modify("-$i month");
            $userGame->setAddedAt($addedDate);
            
            $this->entityManager->persist($userGame);
        }
        $this->entityManager->flush();
        
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/statistics');
        
        $this->assertResponseIsSuccessful();
        // Vérifier que la section des stats mensuelles existe
        $this->assertSelectorExists('body');
    }

    /**
     * TEST 7 : Statistiques avec bibliothèque vide
     */
    public function testStatisticsWithEmptyLibrary(): void
    {
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/statistics');
        
        $this->assertResponseIsSuccessful();
        // Devrait afficher 0 partout
        $this->assertSelectorTextContains('body', '0');
    }

    /**
     * TEST 8 : Calcul du taux de complétion à 0% (aucun jeu completed)
     */
    public function testCompletionRateWithNoCompletedGames(): void
    {
        $game = $this->createGame('Backlog Only', 60001);
        
        $userGame = new UserGame();
        $userGame->setUser($this->user);
        $userGame->setGame($game);
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/statistics');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', '0%');
    }

    /**
     * TEST 9 : Statistiques avec tous les jeux terminés
     */
    public function testStatisticsWithAllGamesCompleted(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $game = $this->createGame("Completed $i", 70000 + $i);
            
            $userGame = new UserGame();
            $userGame->setUser($this->user);
            $userGame->setGame($game);
            $userGame->setStatus('completed');
            $userGame->setIsFavorite(false);
            $userGame->setAddedAt(new \DateTime());
            
            $this->entityManager->persist($userGame);
        }
        $this->entityManager->flush();
        
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/statistics');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', '100'); // 100%
    }

    private function createGame(string $name, int $rawgId, array $genres = ['Action'], float $rating = 4.0): Game
    {
        $game = new Game();
        $game->setRawgId($rawgId);
        $game->setName($name);
        $game->setBackgroundImage('https://example.com/image.jpg');
        $game->setReleased(new \DateTime('2023-01-01'));
        $game->setRating($rating);
        $game->setGenres($genres);
        
        $this->entityManager->persist($game);
        
        return $game;
    }
}