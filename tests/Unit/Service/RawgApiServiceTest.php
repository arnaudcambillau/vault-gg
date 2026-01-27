<?php

namespace App\Tests\Unit\Service;

use App\Service\RawgApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class RawgApiServiceTest extends TestCase
{
    public function testSearchGamesWithValidQuery(): void
    {
        // ARRANGE : Créer une fausse réponse de l'API
        $mockResponse = new MockResponse(json_encode([
            'results' => [
                ['id' => 1, 'name' => 'The Witcher 3'],
                ['id' => 2, 'name' => 'Elden Ring'],
            ]
        ]));
        
        $httpClient = new MockHttpClient($mockResponse);
        $service = new RawgApiService($httpClient, 'fake-api-key');
        
        // ACT : Appeler searchGames
        $result = $service->searchGames('witcher');
        
        // ASSERT : Vérifier qu'on a bien 2 résultats
        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertCount(2, $result['results']);
    }
    
    public function testSearchGamesWithEmptyQuery(): void
    {
        // ARRANGE
        $httpClient = new MockHttpClient();
        $service = new RawgApiService($httpClient, 'fake-api-key');
        
        // ACT : Appeler avec une chaîne vide
        $result = $service->searchGames('');
        
        // ASSERT : Doit retourner un tableau vide
        $this->assertEquals([], $result);
    }
    
    public function testGetGameDetails(): void
    {
        // ARRANGE : Créer une fausse réponse pour les détails d'un jeu
        $mockResponse = new MockResponse(json_encode([
            'id' => 3328,
            'name' => 'The Witcher 3: Wild Hunt',
            'rating' => 4.66,
            'released' => '2015-05-19',
            'background_image' => 'https://example.com/witcher3.jpg',
            'genres' => [
                ['name' => 'Action'],
                ['name' => 'RPG']
            ]
        ]));
        
        $httpClient = new MockHttpClient($mockResponse);
        $service = new RawgApiService($httpClient, 'fake-api-key');
        
        // ACT : Appeler getGameDetails
        $result = $service->getGameDetails(3328);
        
        // ASSERT
        $this->assertIsArray($result);
        $this->assertEquals('The Witcher 3: Wild Hunt', $result['name']);
        $this->assertEquals(4.66, $result['rating']);
        $this->assertArrayHasKey('genres', $result);
    }
    
    public function testSearchGamesUsesCorrectApiKey(): void
    {
        // ARRANGE
        $mockResponse = new MockResponse(json_encode(['results' => []]));
        $httpClient = new MockHttpClient($mockResponse);
        $apiKey = 'test-api-key-123';
        
        $service = new RawgApiService($httpClient, $apiKey);
        
        // ACT
        $service->searchGames('zelda');
        
        // ASSERT : Vérifier que l'API key est utilisée dans la requête
        $requestOptions = $httpClient->getRequestsCount();
        $this->assertEquals(1, $requestOptions);
    }
}
