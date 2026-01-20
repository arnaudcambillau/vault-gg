<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\Entity\UserGame;
use App\Service\RawgApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SearchControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Créer un utilisateur de test
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setUsername('TestUser');
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($this->user, 'password123');
        $this->user->setPassword($hashedPassword);
        $this->user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Nettoyer la base de données
        if ($this->entityManager) {
            $connection = $this->entityManager->getConnection();
            
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            $connection->executeStatement('TRUNCATE TABLE user_game');
            $connection->executeStatement('TRUNCATE TABLE game');
            $connection->executeStatement('TRUNCATE TABLE user');
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }

    public function testSearchPageIsAccessible(): void
    {
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/search');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Rechercher un jeu');
    }

    public function testSearchWithoutQueryShowsEmptyState(): void
    {
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/search');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Commencez à rechercher des jeux');
    }

    public function testSearchWithEmptyQuery(): void
    {
        $this->client->loginUser($this->user);
        
        $crawler = $this->client->request('GET', '/search?q=');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Commencez à rechercher des jeux');
    }

    public function testSearchWithValidQuery(): void
    {
        $this->client->loginUser($this->user);
        
        // Mocker le service RAWG API
        $rawgApiService = $this->createMock(RawgApiService::class);
        $rawgApiService->method('searchGames')->willReturn([
            'results' => [
                [
                    'id' => 12345,
                    'name' => 'Test Game',
                    'background_image' => 'https://example.com/image.jpg',
                    'rating' => 4.5,
                    'genres' => [['name' => 'Action']],
                    'released' => '2023-01-01'
                ]
            ]
        ]);
        
        static::getContainer()->set(RawgApiService::class, $rawgApiService);
        
        $crawler = $this->client->request('GET', '/search?q=test');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Test Game');
    }

    public function testAddGameFromSearch(): void
    {
        $this->client->loginUser($this->user);
        
        // Mocker le service RAWG API
        $rawgApiService = $this->createMock(RawgApiService::class);
        $rawgApiService->method('getGameDetails')->willReturn([
            'id' => 12345,
            'name' => 'New Game',
            'background_image' => 'https://example.com/image.jpg',
            'rating' => 4.5,
            'genres' => [['name' => 'Action'], ['name' => 'Adventure']],
            'released' => '2023-01-01'
        ]);
        
        static::getContainer()->set(RawgApiService::class, $rawgApiService);
        
        $this->client->request('POST', '/search/add/12345');
        
        $this->assertResponseRedirects('/search');
        
        // Vérifier que le jeu a été ajouté à la bibliothèque
        $userGame = $this->entityManager->getRepository(UserGame::class)->findOneBy([
            'user' => $this->user
        ]);
        
        $this->assertNotNull($userGame);
        $this->assertEquals('New Game', $userGame->getGame()->getName());
        $this->assertEquals('backlog', $userGame->getStatus());
    }

    public function testAddGameAlreadyInLibrary(): void
    {
        $this->client->loginUser($this->user);
        
        // ✅ ÉTAPE 1 : Créer un jeu déjà dans la bibliothèque
        $game = new Game();
        $game->setRawgId(12345);
        $game->setName('Existing Game');
        $game->setBackgroundImage('https://example.com/image.jpg');
        $game->setRating(4.5);
        $game->setGenres(['Action']);
        $game->setReleased(new \DateTime('2023-01-01'));
        
        $this->entityManager->persist($game);
        
        $userGame = new UserGame();
        $userGame->setUser($this->user);
        $userGame->setGame($game);
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        // ✅ ÉTAPE 2 : Mocker le service RAWG API
        $rawgApiService = $this->createMock(RawgApiService::class);
        $rawgApiService->method('getGameDetails')->willReturn([
            'id' => 12345,
            'name' => 'Existing Game',
            'background_image' => 'https://example.com/image.jpg',
            'rating' => 4.5,
            'genres' => [['name' => 'Action']],
            'released' => '2023-01-01'
        ]);
        
        static::getContainer()->set(RawgApiService::class, $rawgApiService);
        
        // ✅ ÉTAPE 3 : Essayer d'ajouter le jeu à nouveau
        $this->client->request('POST', '/search/add/12345');
        
        $this->assertResponseRedirects('/search');
        
        $this->client->followRedirect();
        
        // ✅ Vérifier le message d'avertissement avec le bon sélecteur
        $this->assertSelectorExists('[class*="bg-amber-500"]');
        $this->assertSelectorTextContains('.p-4', 'déjà dans votre bibliothèque');
    }

    public function testAddGameWithoutAuthentication(): void
    {
        // Ne pas se connecter
        
        $this->client->request('POST', '/search/add/12345');
        
        // Doit rediriger vers la page de connexion
        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $this->client->getResponse()->headers->get('Location'));
    }

    public function testSearchWithMultipleResults(): void
    {
        $this->client->loginUser($this->user);
        
        // Mocker le service RAWG API avec plusieurs résultats
        $rawgApiService = $this->createMock(RawgApiService::class);
        $rawgApiService->method('searchGames')->willReturn([
            'results' => [
                [
                    'id' => 1,
                    'name' => 'Game One',
                    'background_image' => 'https://example.com/image1.jpg',
                    'rating' => 4.5,
                    'genres' => [['name' => 'Action']],
                    'released' => '2023-01-01'
                ],
                [
                    'id' => 2,
                    'name' => 'Game Two',
                    'background_image' => 'https://example.com/image2.jpg',
                    'rating' => 4.0,
                    'genres' => [['name' => 'RPG']],
                    'released' => '2023-02-01'
                ],
                [
                    'id' => 3,
                    'name' => 'Game Three',
                    'background_image' => 'https://example.com/image3.jpg',
                    'rating' => 3.5,
                    'genres' => [['name' => 'Adventure']],
                    'released' => '2023-03-01'
                ]
            ]
        ]);
        
        static::getContainer()->set(RawgApiService::class, $rawgApiService);
        
        $crawler = $this->client->request('GET', '/search?q=game');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Game One');
        $this->assertSelectorTextContains('body', 'Game Two');
        $this->assertSelectorTextContains('body', 'Game Three');
    }

    public function testAddNewGameCreatesGameEntity(): void
    {
        $this->client->loginUser($this->user);
        
        // Mocker le service RAWG API
        $rawgApiService = $this->createMock(RawgApiService::class);
        $rawgApiService->method('getGameDetails')->willReturn([
            'id' => 99999,
            'name' => 'Brand New Game',
            'background_image' => 'https://example.com/new-game.jpg',
            'rating' => 4.8,
            'genres' => [['name' => 'Strategy']],
            'released' => '2024-01-01'
        ]);
        
        static::getContainer()->set(RawgApiService::class, $rawgApiService);
        
        $this->client->request('POST', '/search/add/99999');
        
        $this->assertResponseRedirects('/search');
        
        // Vérifier que l'entité Game a été créée
        $game = $this->entityManager->getRepository(Game::class)->findOneBy([
            'rawgId' => 99999
        ]);
        
        $this->assertNotNull($game);
        $this->assertEquals('Brand New Game', $game->getName());
        $this->assertEquals(4.8, $game->getRating());
    }

    public function testSearchWithNoResults(): void
    {
        $this->client->loginUser($this->user);
        
        // Mocker le service RAWG API sans résultats
        $rawgApiService = $this->createMock(RawgApiService::class);
        $rawgApiService->method('searchGames')->willReturn([
            'results' => []
        ]);
        
        static::getContainer()->set(RawgApiService::class, $rawgApiService);
        
        $crawler = $this->client->request('GET', '/search?q=nonexistent');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Aucun jeu trouvé');
    }
}