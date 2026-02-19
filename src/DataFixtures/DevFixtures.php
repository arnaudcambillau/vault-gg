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
        // 1. JEUX (14 jeux, sans God of War)
        // =============================================
        $gamesData = [
            [   // index 0
                'rawgId'          => 3498,
                'name'            => 'Grand Theft Auto V',
                'backgroundImage' => 'https://media.rawg.io/media/games/456/456dea5e1c7e3cd07060c14e96612001.jpg',
                'released'        => new \DateTime('2013-09-17'),
                'rating'          => 4.47,
                'genres'          => ['Action', 'Adventure'],
            ],
            [   // index 1
                'rawgId'          => 4200,
                'name'            => 'Portal 2',
                'backgroundImage' => 'https://media.rawg.io/media/games/328/3283617cb7d75d67257fc58339188742.jpg',
                'released'        => new \DateTime('2011-04-19'),
                'rating'          => 4.62,
                'genres'          => ['Puzzle', 'Platformer'],
            ],
            [   // index 2
                'rawgId'          => 5679,
                'name'            => 'The Elder Scrolls V: Skyrim',
                'backgroundImage' => 'https://media.rawg.io/media/games/7cf/7cfc9220b401b7a300e409e539c9afd5.jpg',
                'released'        => new \DateTime('2011-11-11'),
                'rating'          => 4.42,
                'genres'          => ['RPG', 'Action'],
            ],
            [   // index 3
                'rawgId'          => 32,
                'name'            => 'Destiny 2',
                'backgroundImage' => 'https://media.rawg.io/media/games/34b/34b1f1850a1c06fd971bc6ab3ac0ce0e.jpg',
                'released'        => new \DateTime('2017-09-06'),
                'rating'          => 3.44,
                'genres'          => ['Shooter', 'RPG'],
            ],
            [   // index 4
                'rawgId'          => 41494,
                'name'            => 'Cyberpunk 2077',
                'backgroundImage' => 'https://media.rawg.io/media/games/26d/26d4437715bee60138dab4a7c8c59c92.jpg',
                'released'        => new \DateTime('2020-12-10'),
                'rating'          => 4.12,
                'genres'          => ['RPG', 'Action', 'Adventure'],
            ],
            [   // index 5
                'rawgId'          => 3272,
                'name'            => 'Rocket League',
                'backgroundImage' => 'https://media.rawg.io/media/games/8cc/8cce7c0e99dcc43d66c8efd42f9d03e3.jpg',
                'released'        => new \DateTime('2015-07-07'),
                'rating'          => 3.84,
                'genres'          => ['Sports', 'Racing'],
            ],
            [   // index 6
                'rawgId'          => 28,
                'name'            => 'Red Dead Redemption 2',
                'backgroundImage' => 'https://media.rawg.io/media/games/511/5118aff5091cb3efec399c808f8c598f.jpg',
                'released'        => new \DateTime('2018-10-26'),
                'rating'          => 4.57,
                'genres'          => ['Action', 'Adventure'],
            ],
            [   // index 7
                'rawgId'          => 12020,
                'name'            => 'Left 4 Dead 2',
                'backgroundImage' => 'https://media.rawg.io/media/games/d58/d588947d4286e7b5e0e12af9300f96d2.jpg',
                'released'        => new \DateTime('2009-11-17'),
                'rating'          => 4.17,
                'genres'          => ['Shooter', 'Action'],
            ],
            [   // index 8
                'rawgId'          => 3439,
                'name'            => 'The Witcher 3: Wild Hunt',
                'backgroundImage' => 'https://media.rawg.io/media/games/618/618c2031a07bbff6b4f611f10b6bcdbc.jpg',
                'released'        => new \DateTime('2015-05-18'),
                'rating'          => 4.66,
                'genres'          => ['RPG', 'Action'],
            ],
            [   // index 9
                'rawgId'          => 13536,
                'name'            => 'Minecraft',
                'backgroundImage' => 'https://media.rawg.io/media/games/b4e/b4e4c73d5aa4ec66bbf75375c4847a2b.jpg',
                'released'        => new \DateTime('2009-05-10'),
                'rating'          => 4.37,
                'genres'          => ['Sandbox', 'Adventure'],
            ],
            [   // index 10
                'rawgId'          => 58134,
                'name'            => 'Hollow Knight',
                'backgroundImage' => 'https://media.rawg.io/media/games/4cf/4cfc6b7f1850590a4634b08bfab308ab.jpg',
                'released'        => new \DateTime('2017-02-24'),
                'rating'          => 4.41,
                'genres'          => ['Action', 'Indie'],
            ],
            [   // index 11
                'rawgId'          => 278522,
                'name'            => 'Elden Ring',
                'backgroundImage' => 'https://media.rawg.io/media/games/b29/b294fdd866dcdb643e7bab370a552855.jpg',
                'released'        => new \DateTime('2022-02-25'),
                'rating'          => 4.55,
                'genres'          => ['RPG', 'Action'],
            ],
            [   // index 12
                'rawgId'          => 11859,
                'name'            => 'Team Fortress 2',
                'backgroundImage' => 'https://media.rawg.io/media/games/f87/f87457e8347484033cb34cde6101d08d.jpg',
                'released'        => new \DateTime('2007-10-10'),
                'rating'          => 3.92,
                'genres'          => ['Shooter', 'Action'],
            ],
            [   // index 13
                'rawgId'          => 3070,
                'name'            => 'Battlefield 1',
                'backgroundImage' => 'https://media.rawg.io/media/games/bc0/bc06a29ceac58652b684deefe7d56099.jpg',
                'released'        => new \DateTime('2016-10-21'),
                'rating'          => 3.79,
                'genres'          => ['Shooter', 'Action'],
            ],
        ];

        $games = [];
        foreach ($gamesData as $data) {
            $game = new Game();
            $game->setRawgId($data['rawgId']);
            $game->setName($data['name']);
            $game->setBackgroundImage($data['backgroundImage']);
            $game->setReleased($data['released']);
            $game->setRating($data['rating']);
            $game->setGenres($data['genres']);
            $manager->persist($game);
            $games[] = $game;
        }

        // =============================================
        // 2. UTILISATEURS (10 users)
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
        // =============================================
        $userGamesData = [

            // kpuchs ADMIN — 8 jeux
            ['user' => 0, 'game' => 0,  'status' => 'completed',   'favorite' => true],
            ['user' => 0, 'game' => 1,  'status' => 'completed',   'favorite' => false],
            ['user' => 0, 'game' => 4,  'status' => 'in_progress', 'favorite' => true],
            ['user' => 0, 'game' => 6,  'status' => 'completed',   'favorite' => true],
            ['user' => 0, 'game' => 8,  'status' => 'completed',   'favorite' => true],
            ['user' => 0, 'game' => 11, 'status' => 'in_progress', 'favorite' => false],
            ['user' => 0, 'game' => 12, 'status' => 'backlog',     'favorite' => false],
            ['user' => 0, 'game' => 13, 'status' => 'completed',   'favorite' => false],

            // Alice — 5 jeux
            ['user' => 1, 'game' => 0,  'status' => 'completed',   'favorite' => true],
            ['user' => 1, 'game' => 3,  'status' => 'in_progress', 'favorite' => false],
            ['user' => 1, 'game' => 5,  'status' => 'backlog',     'favorite' => false],
            ['user' => 1, 'game' => 9,  'status' => 'completed',   'favorite' => true],
            ['user' => 1, 'game' => 10, 'status' => 'in_progress', 'favorite' => false],

            // Bob — 6 jeux
            ['user' => 2, 'game' => 1,  'status' => 'completed',   'favorite' => true],
            ['user' => 2, 'game' => 2,  'status' => 'completed',   'favorite' => false],
            ['user' => 2, 'game' => 4,  'status' => 'in_progress', 'favorite' => true],
            ['user' => 2, 'game' => 7,  'status' => 'completed',   'favorite' => false],
            ['user' => 2, 'game' => 11, 'status' => 'backlog',     'favorite' => false],
            ['user' => 2, 'game' => 13, 'status' => 'completed',   'favorite' => false],

            // Charlie — 4 jeux
            ['user' => 3, 'game' => 0,  'status' => 'backlog',     'favorite' => false],
            ['user' => 3, 'game' => 3,  'status' => 'in_progress', 'favorite' => false],
            ['user' => 3, 'game' => 9,  'status' => 'completed',   'favorite' => true],
            ['user' => 3, 'game' => 12, 'status' => 'completed',   'favorite' => false],

            // David — 7 jeux
            ['user' => 4, 'game' => 0,  'status' => 'completed',   'favorite' => true],
            ['user' => 4, 'game' => 2,  'status' => 'completed',   'favorite' => false],
            ['user' => 4, 'game' => 6,  'status' => 'completed',   'favorite' => true],
            ['user' => 4, 'game' => 8,  'status' => 'in_progress', 'favorite' => false],
            ['user' => 4, 'game' => 10, 'status' => 'backlog',     'favorite' => false],
            ['user' => 4, 'game' => 11, 'status' => 'completed',   'favorite' => true],
            ['user' => 4, 'game' => 13, 'status' => 'backlog',     'favorite' => false],

            // Emma — 5 jeux
            ['user' => 5, 'game' => 1,  'status' => 'completed',   'favorite' => true],
            ['user' => 5, 'game' => 4,  'status' => 'backlog',     'favorite' => false],
            ['user' => 5, 'game' => 6,  'status' => 'completed',   'favorite' => true],
            ['user' => 5, 'game' => 8,  'status' => 'completed',   'favorite' => false],
            ['user' => 5, 'game' => 10, 'status' => 'in_progress', 'favorite' => false],

            // Florian — 8 jeux
            ['user' => 6, 'game' => 0,  'status' => 'in_progress', 'favorite' => false],
            ['user' => 6, 'game' => 2,  'status' => 'backlog',     'favorite' => false],
            ['user' => 6, 'game' => 4,  'status' => 'completed',   'favorite' => true],
            ['user' => 6, 'game' => 5,  'status' => 'in_progress', 'favorite' => false],
            ['user' => 6, 'game' => 7,  'status' => 'completed',   'favorite' => false],
            ['user' => 6, 'game' => 9,  'status' => 'completed',   'favorite' => true],
            ['user' => 6, 'game' => 11, 'status' => 'completed',   'favorite' => true],
            ['user' => 6, 'game' => 12, 'status' => 'backlog',     'favorite' => false],

            // Ghost — 6 jeux
            ['user' => 7, 'game' => 0,  'status' => 'completed',   'favorite' => true],
            ['user' => 7, 'game' => 3,  'status' => 'completed',   'favorite' => false],
            ['user' => 7, 'game' => 7,  'status' => 'in_progress', 'favorite' => false],
            ['user' => 7, 'game' => 12, 'status' => 'completed',   'favorite' => true],
            ['user' => 7, 'game' => 13, 'status' => 'completed',   'favorite' => false],
            ['user' => 7, 'game' => 6,  'status' => 'backlog',     'favorite' => false],

            // Hugo — 3 jeux
            ['user' => 8, 'game' => 2,  'status' => 'backlog',     'favorite' => false],
            ['user' => 8, 'game' => 8,  'status' => 'in_progress', 'favorite' => true],
            ['user' => 8, 'game' => 11, 'status' => 'completed',   'favorite' => true],

            // Ines — 9 jeux
            ['user' => 9, 'game' => 0,  'status' => 'completed',   'favorite' => true],
            ['user' => 9, 'game' => 1,  'status' => 'completed',   'favorite' => false],
            ['user' => 9, 'game' => 2,  'status' => 'in_progress', 'favorite' => false],
            ['user' => 9, 'game' => 4,  'status' => 'completed',   'favorite' => true],
            ['user' => 9, 'game' => 6,  'status' => 'backlog',     'favorite' => false],
            ['user' => 9, 'game' => 8,  'status' => 'completed',   'favorite' => true],
            ['user' => 9, 'game' => 9,  'status' => 'in_progress', 'favorite' => false],
            ['user' => 9, 'game' => 11, 'status' => 'completed',   'favorite' => true],
            ['user' => 9, 'game' => 13, 'status' => 'backlog',     'favorite' => false],
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