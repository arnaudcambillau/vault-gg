<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        // ARRANGE : Créer un utilisateur
        $user = new User();
        $user->setEmail('test@vault.gg');
        $user->setUsername('TestUser');
        $user->setPassword('hashedpassword123');
        
        // ASSERT : Vérifier que ça fonctionne
        $this->assertEquals('test@vault.gg', $user->getEmail());
        $this->assertEquals('TestUser', $user->getUsername());
        $this->assertEquals('hashedpassword123', $user->getPassword());
    }
    
    public function testUserHasDefaultRoleUser(): void
    {
        // ARRANGE
        $user = new User();
        
        // ASSERT : Un user doit avoir ROLE_USER par défaut
        $this->assertContains('ROLE_USER', $user->getRoles());
    }
}
