<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test@vault.gg');
        $user->setUsername('TestUser');
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'testpassword123');
        $user->setPassword($hashedPassword);

        $manager->persist($user);
        $manager->flush();
    }
}
