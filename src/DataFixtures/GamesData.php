<?php

namespace App\DataFixtures;

class GamesData
{
    public const GAMES = [
        [   // index 0
            'rawgId'          => 3498,
            'name'            => 'Grand Theft Auto V',
            'backgroundImage' => 'https://media.rawg.io/media/games/456/456dea5e1c7e3cd07060c14e96612001.jpg',
            'released'        => '2013-09-17',
            'rating'          => 4.47,
            'genres'          => ['Action', 'Adventure'],
        ],
        [   // index 1
            'rawgId'          => 4200,
            'name'            => 'Portal 2',
            'backgroundImage' => 'https://media.rawg.io/media/games/328/3283617cb7d75d67257fc58339188742.jpg',
            'released'        => '2011-04-19',
            'rating'          => 4.62,
            'genres'          => ['Puzzle', 'Platformer'],
        ],
        [   // index 2
            'rawgId'          => 5679,
            'name'            => 'The Elder Scrolls V: Skyrim',
            'backgroundImage' => 'https://media.rawg.io/media/games/7cf/7cfc9220b401b7a300e409e539c9afd5.jpg',
            'released'        => '2011-11-11',
            'rating'          => 4.42,
            'genres'          => ['RPG', 'Action'],
        ],
        [   // index 3
            'rawgId'          => 32,
            'name'            => 'Destiny 2',
            'backgroundImage' => 'https://media.rawg.io/media/games/34b/34b1f1850a1c06fd971bc6ab3ac0ce0e.jpg',
            'released'        => '2017-09-06',
            'rating'          => 3.44,
            'genres'          => ['Shooter', 'RPG'],
        ],
        [   // index 4
            'rawgId'          => 41494,
            'name'            => 'Cyberpunk 2077',
            'backgroundImage' => 'https://media.rawg.io/media/games/26d/26d4437715bee60138dab4a7c8c59c92.jpg',
            'released'        => '2020-12-10',
            'rating'          => 4.12,
            'genres'          => ['RPG', 'Action', 'Adventure'],
        ],
        [   // index 5
            'rawgId'          => 3272,
            'name'            => 'Rocket League',
            'backgroundImage' => 'https://media.rawg.io/media/games/8cc/8cce7c0e99dcc43d66c8efd42f9d03e3.jpg',
            'released'        => '2015-07-07',
            'rating'          => 3.84,
            'genres'          => ['Sports', 'Racing'],
        ],
        [   // index 6
            'rawgId'          => 28,
            'name'            => 'Red Dead Redemption 2',
            'backgroundImage' => 'https://media.rawg.io/media/games/511/5118aff5091cb3efec399c808f8c598f.jpg',
            'released'        => '2018-10-26',
            'rating'          => 4.57,
            'genres'          => ['Action', 'Adventure'],
        ],
        [   // index 7 — ✅ rawgId corrigé
            'rawgId'          => 3328,
            'name'            => 'The Witcher 3: Wild Hunt',
            'backgroundImage' => 'https://media.rawg.io/media/games/618/618c2031a07bbff6b4f611f10b6bcdbc.jpg',
            'released'        => '2015-05-18',
            'rating'          => 4.66,
            'genres'          => ['RPG', 'Action'],
        ],
        [   // index 8 — ✅ rawgId corrigé
            'rawgId'          => 326243,
            'name'            => 'Elden Ring',
            'backgroundImage' => 'https://media.rawg.io/media/games/b29/b294fdd866dcdb643e7bab370a552855.jpg',
            'released'        => '2022-02-25',
            'rating'          => 4.55,
            'genres'          => ['RPG', 'Action'],
        ],
    ];
}