<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\User;
use App\Entity\UserGame;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DevFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public static function getGroups(): array
    {
        return ['dev', 'all'];
    }

    public function load(ObjectManager $manager): void
    {
        // =============================================
        // 1. JEUX — source unique GamesData
        // =============================================
        $games = [];
        foreach (GamesData::GAMES as $data) {
            $game = new Game();
            $game->setRawgId($data['rawgId']);
            $game->setName($data['name']);
            $game->setBackgroundImage($data['backgroundImage']);
            $game->setReleased(new \DateTime($data['released']));
            $game->setRating($data['rating']);
            $game->setGenres($data['genres']);
            $manager->persist($game);
            $games[] = $game;
        }

        // =============================================
        // 2. UTILISATEURS
        // =============================================
        $usersData = [
            ['email' => 'kpuchs@vault.gg',   'username' => 'kpuchs',      'roles' => ['ROLE_ADMIN'], 'password' => 'vault66'],
            ['email' => 'alice@vault.gg',    'username' => 'AliceGamer',  'roles' => [],             'password' => 'Alice1234!'],
            ['email' => 'bob@vault.gg',      'username' => 'BobPlays',    'roles' => [],             'password' => 'Bob1234!'],
            ['email' => 'charlie@vault.gg',  'username' => 'CharlieX',    'roles' => [],             'password' => 'Charlie1234!'],
            ['email' => 'david@vault.gg',    'username' => 'DavidGG',     'roles' => [],             'password' => 'David1234!'],
            ['email' => 'emma@vault.gg',     'username' => 'EmmaPlays',   'roles' => [],             'password' => 'Emma1234!'],
            ['email' => 'florian@vault.gg',  'username' => 'FlorianX',    'roles' => [],             'password' => 'Florian1234!'],
            ['email' => 'ghost@vault.gg',    'username' => 'GhostSniper', 'roles' => [],             'password' => 'Ghost1234!'],
            ['email' => 'hugo@vault.gg',     'username' => 'HugoVault',   'roles' => [],             'password' => 'Hugo1234!'],
            ['email' => 'ines@vault.gg',     'username' => 'InesGames',   'roles' => [],             'password' => 'Ines1234!'],
        ];

        $users = [];
        foreach ($usersData as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setUsername($data['username']);
            $user->setRoles($data['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
            $manager->persist($user);
            $users[] = $user;
        }

        // =============================================
        // 3. BIBLIOTHÈQUES (UserGame)
        // Index : 0=GTA5, 1=Portal2, 2=Skyrim, 3=Destiny2
        //         4=Cyberpunk, 5=RocketLeague, 6=RDR2
        //         7=Witcher3, 8=EldenRing
        // =============================================
        $userGamesData = [
            // kpuchs admin — 7 jeux
            ['user' => 0, 'game' => 0, 'status' => 'completed',   'favorite' => true],
            ['user' => 0, 'game' => 1, 'status' => 'completed',   'favorite' => false],
            ['user' => 0, 'game' => 2, 'status' => 'completed',   'favorite' => false],
            ['user' => 0, 'game' => 3, 'status' => 'in_progress', 'favorite' => false],
            ['user' => 0, 'game' => 4, 'status' => 'in_progress', 'favorite' => true],
            ['user' => 0, 'game' => 5, 'status' => 'backlog',     'favorite' => false],
            ['user' => 0, 'game' => 6, 'status' => 'completed',   'favorite' => true],
            ['user' => 0, 'game' => 7, 'status' => 'completed',   'favorite' => true],
            ['user' => 0, 'game' => 8, 'status' => 'in_progress', 'favorite' => false],
            // Alice — 5 jeux
            ['user' => 1, 'game' => 0, 'status' => 'completed',   'favorite' => true],
            ['user' => 1, 'game' => 3, 'status' => 'in_progress', 'favorite' => false],
            ['user' => 1, 'game' => 5, 'status' => 'backlog',     'favorite' => false],
            ['user' => 1, 'game' => 7, 'status' => 'completed',   'favorite' => true],
            ['user' => 1, 'game' => 8, 'status' => 'backlog',     'favorite' => false],
            // Bob — 5 jeux
            ['user' => 2, 'game' => 1, 'status' => 'completed',   'favorite' => true],
            ['user' => 2, 'game' => 2, 'status' => 'completed',   'favorite' => false],
            ['user' => 2, 'game' => 4, 'status' => 'in_progress', 'favorite' => true],
            ['user' => 2, 'game' => 6, 'status' => 'completed',   'favorite' => true],
            ['user' => 2, 'game' => 8, 'status' => 'backlog',     'favorite' => false],
            // Charlie — 3 jeux
            ['user' => 3, 'game' => 0, 'status' => 'backlog',     'favorite' => false],
            ['user' => 3, 'game' => 3, 'status' => 'in_progress', 'favorite' => false],
            ['user' => 3, 'game' => 6, 'status' => 'completed',   'favorite' => true],
            // David — 6 jeux
            ['user' => 4, 'game' => 0, 'status' => 'completed',   'favorite' => true],
            ['user' => 4, 'game' => 2, 'status' => 'completed',   'favorite' => false],
            ['user' => 4, 'game' => 4, 'status' => 'in_progress', 'favorite' => false],
            ['user' => 4, 'game' => 6, 'status' => 'completed',   'favorite' => true],
            ['user' => 4, 'game' => 7, 'status' => 'completed',   'favorite' => true],
            ['user' => 4, 'game' => 8, 'status' => 'completed',   'favorite' => false],
            // Emma — 4 jeux
            ['user' => 5, 'game' => 1, 'status' => 'completed',   'favorite' => true],
            ['user' => 5, 'game' => 4, 'status' => 'backlog',     'favorite' => false],
            ['user' => 5, 'game' => 6, 'status' => 'completed',   'favorite' => true],
            ['user' => 5, 'game' => 7, 'status' => 'in_progress', 'favorite' => false],
            // Florian — 6 jeux
            ['user' => 6, 'game' => 0, 'status' => 'in_progress', 'favorite' => false],
            ['user' => 6, 'game' => 2, 'status' => 'backlog',     'favorite' => false],
            ['user' => 6, 'game' => 4, 'status' => 'completed',   'favorite' => true],
            ['user' => 6, 'game' => 5, 'status' => 'in_progress', 'favorite' => false],
            ['user' => 6, 'game' => 7, 'status' => 'completed',   'favorite' => true],
            ['user' => 6, 'game' => 8, 'status' => 'completed',   'favorite' => true],
            // Ghost — 4 jeux
            ['user' => 7, 'game' => 0, 'status' => 'completed',   'favorite' => true],
            ['user' => 7, 'game' => 3, 'status' => 'completed',   'favorite' => false],
            ['user' => 7, 'game' => 6, 'status' => 'backlog',     'favorite' => false],
            ['user' => 7, 'game' => 8, 'status' => 'in_progress', 'favorite' => false],
            // Hugo — 3 jeux
            ['user' => 8, 'game' => 2, 'status' => 'backlog',     'favorite' => false],
            ['user' => 8, 'game' => 7, 'status' => 'in_progress', 'favorite' => true],
            ['user' => 8, 'game' => 8, 'status' => 'completed',   'favorite' => true],
            // Ines — 7 jeux
            ['user' => 9, 'game' => 0, 'status' => 'completed',   'favorite' => true],
            ['user' => 9, 'game' => 1, 'status' => 'completed',   'favorite' => false],
            ['user' => 9, 'game' => 2, 'status' => 'in_progress', 'favorite' => false],
            ['user' => 9, 'game' => 4, 'status' => 'completed',   'favorite' => true],
            ['user' => 9, 'game' => 6, 'status' => 'backlog',     'favorite' => false],
            ['user' => 9, 'game' => 7, 'status' => 'completed',   'favorite' => true],
            ['user' => 9, 'game' => 8, 'status' => 'completed',   'favorite' => true],
        ];

        foreach ($userGamesData as $data) {
            $userGame = new UserGame();
            $userGame->setUser($users[$data['user']]);
            $userGame->setGame($games[$data['game']]);
            $userGame->setStatus($data['status']);
            $userGame->setIsFavorite($data['favorite']);
            $userGame->setAddedAt(new \DateTime('-' . rand(1, 365) . ' days'));
            $manager->persist($userGame);
        }

        $manager->flush();
    }
}