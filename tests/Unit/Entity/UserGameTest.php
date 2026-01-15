<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\Game;
use App\Entity\UserGame;
use PHPUnit\Framework\TestCase;

class UserGameTest extends TestCase
{
    public function testUserGameCreation(): void
    {
        // ARRANGE : Créer un UserGame
        $userGame = new UserGame();
        $userGame->setStatus('backlog');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        // ASSERT
        $this->assertEquals('backlog', $userGame->getStatus());
        $this->assertFalse($userGame->isFavorite());
        $this->assertInstanceOf(\DateTime::class, $userGame->getAddedAt());
    }
    
    public function testUserGameStatuses(): void
    {
        // ARRANGE
        $userGame = new UserGame();
        
        // TEST : Statut backlog
        $userGame->setStatus('backlog');
        $this->assertEquals('backlog', $userGame->getStatus());
        
        // TEST : Statut in_progress
        $userGame->setStatus('in_progress');
        $this->assertEquals('in_progress', $userGame->getStatus());
        
        // TEST : Statut completed
        $userGame->setStatus('completed');
        $this->assertEquals('completed', $userGame->getStatus());
    }
    
    public function testUserGameFavorite(): void
    {
        // ARRANGE
        $userGame = new UserGame();
        
        // TEST : Ajouter aux favoris
        $userGame->setIsFavorite(true);
        $this->assertTrue($userGame->isFavorite());
        
        // TEST : Retirer des favoris
        $userGame->setIsFavorite(false);
        $this->assertFalse($userGame->isFavorite());
    }
    
    public function testUserGameRelations(): void
    {
        // ARRANGE : Créer un User, un Game et un UserGame
        $user = new User();
        $user->setEmail('test@vault.gg');
        $user->setUsername('TestUser');
        
        $game = new Game();
        $game->setName('Elden Ring');
        $game->setRawgId(999);
        
        $userGame = new UserGame();
        $userGame->setUser($user);
        $userGame->setGame($game);
        
        // ASSERT : Les relations doivent être correctes
        $this->assertSame($user, $userGame->getUser());
        $this->assertSame($game, $userGame->getGame());
    }
}
