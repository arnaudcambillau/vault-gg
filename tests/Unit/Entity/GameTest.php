<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Game;
use PHPUnit\Framework\TestCase;

class GameTest extends TestCase
{
    public function testGameCreation(): void
    {
        // ARRANGE : Créer un jeu
        $game = new Game();
        $game->setRawgId(12345);
        $game->setName('The Witcher 3');
        $game->setBackgroundImage('https://example.com/witcher3.jpg');
        $game->setRating(4.5);
        
        // ASSERT : Vérifier que tout fonctionne
        $this->assertEquals(12345, $game->getRawgId());
        $this->assertEquals('The Witcher 3', $game->getName());
        $this->assertEquals('https://example.com/witcher3.jpg', $game->getBackgroundImage());
        $this->assertEquals(4.5, $game->getRating());
    }
    
    public function testGameWithReleasedDate(): void
    {
        // ARRANGE
        $game = new Game();
        $releaseDate = new \DateTime('2015-05-19');
        $game->setReleased($releaseDate);
        
        // ASSERT : La date doit être correcte
        $this->assertEquals('2015-05-19', $game->getReleased()->format('Y-m-d'));
    }
    
    public function testGameGenres(): void
    {
        // ARRANGE
        $game = new Game();
        $genres = ['Action', 'RPG', 'Adventure'];
        $game->setGenres($genres);
        
        // ASSERT : Les genres doivent être stockés en array
        $this->assertEquals($genres, $game->getGenres());
        $this->assertIsArray($game->getGenres());
        $this->assertContains('RPG', $game->getGenres());
    }
    
    public function testGameWithoutGenres(): void
    {
        // ARRANGE
        $game = new Game();
        
        // ASSERT : Un jeu peut ne pas avoir de genres
        $this->assertNull($game->getGenres());
    }
}
