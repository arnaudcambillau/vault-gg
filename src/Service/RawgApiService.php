<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RawgApiService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;
    private string $baseUrl = 'https://api.rawg.io/api';

    public function __construct(HttpClientInterface $httpClient, string $rawgApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $rawgApiKey;
    }

    /* Rechercher des jeux par nom */
    public function searchGames(string $query, int $page = 1, int $pageSize = 20): array
    {
        if (empty($query)) {
            return [];
        }

        $response = $this->httpClient->request('GET', $this->baseUrl . '/games', [
            'query' => [
                'key' => $this->apiKey,
                'search' => $query,
                'page' => $page,
                'page_size' => $pageSize,
            ]
        ]);

        return $response->toArray();
    }

    /* Récupérer les détails d'un jeu par son ID RAWG */
    public function getGameDetails(int $rawgId): array
    {
        $response = $this->httpClient->request('GET', $this->baseUrl . '/games/' . $rawgId, [
            'query' => [
                'key' => $this->apiKey,
            ]
        ]);

        return $response->toArray();
    }
}