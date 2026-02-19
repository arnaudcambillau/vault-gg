<?php

namespace App\DataFixtures;

use App\Document\Review;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectManager;

class MongoFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private DocumentManager $dm)
    {
    }

    public static function getGroups(): array
    {
        return ['mongo', 'all'];
    }

    public function load(ObjectManager $manager): void
    {
        // Vider la collection avant d'insérer
        $this->dm->getDocumentCollection(Review::class)->deleteMany([]);

        $reviewsData = [
            // GTA V (rawgId: 3498)
            [
                'userId'       => 2, // Alice
                'username'     => 'AliceGamer',
                'userAvatar'   => null,
                'gameId'       => 3498,
                'gameName'     => 'Grand Theft Auto V',
                'rating'       => 5,
                'title'        => 'Un chef-d\'œuvre absolu',
                'content'      => 'Ce jeu est incroyable, des heures et des heures de gameplay. Le mode histoire est captivant et le online est infini.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
            [
                'userId'       => 5, // David
                'username'     => 'DavidGG',
                'userAvatar'   => null,
                'gameId'       => 3498,
                'gameName'     => 'Grand Theft Auto V',
                'rating'       => 4,
                'title'        => 'Toujours aussi bon 10 ans après',
                'content'      => 'Rockstar a vraiment livré un jeu intemporel. Quelques mécaniques vieillissantes mais le contenu est colossal.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
            // Portal 2 (rawgId: 4200)
            [
                'userId'       => 2,
                'username'     => 'AliceGamer',
                'userAvatar'   => null,
                'gameId'       => 4200,
                'gameName'     => 'Portal 2',
                'rating'       => 5,
                'title'        => 'Le meilleur puzzle-game de tous les temps',
                'content'      => 'La narration, les mécaniques, l\'humour... Portal 2 est parfait de bout en bout. Le co-op est une expérience unique.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
            [
                'userId'       => 7, // Florian
                'username'     => 'FlorianX',
                'userAvatar'   => null,
                'gameId'       => 4200,
                'gameName'     => 'Portal 2',
                'rating'       => 5,
                'title'        => 'GlaDOS est le meilleur personnage du jeu vidéo',
                'content'      => 'L\'écriture est brillante, les puzzles sont parfaitement dosés. Un jeu que tout le monde devrait faire.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
            // Skyrim (rawgId: 5679)
            [
                'userId'       => 3, // Bob
                'username'     => 'BobPlays',
                'userAvatar'   => null,
                'gameId'       => 5679,
                'gameName'     => 'The Elder Scrolls V: Skyrim',
                'rating'       => 4,
                'title'        => 'Un monde ouvert épique',
                'content'      => 'Skyrim reste une référence en termes de monde ouvert. Quelques bugs certes, mais l\'immersion est totale.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
            // Cyberpunk 2077 (rawgId: 41494)
            [
                'userId'       => 4, // Charlie
                'username'     => 'CharlieX',
                'userAvatar'   => null,
                'gameId'       => 41494,
                'gameName'     => 'Cyberpunk 2077',
                'rating'       => 3,
                'title'        => 'Bon jeu, lancement catastrophique',
                'content'      => 'Le lore est fascinant et Night City est magnifique. Maintenant bien patchés, les bugs ont largement disparu.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
            [
                'userId'       => 8, // Hugo
                'username'     => 'HugoVault',
                'userAvatar'   => null,
                'gameId'       => 41494,
                'gameName'     => 'Cyberpunk 2077',
                'rating'       => 5,
                'title'        => 'Après les patches, un chef-d\'œuvre',
                'content'      => 'J\'ai attendu 2 ans pour y jouer et je n\'ai pas été déçu. L\'histoire de V est captivante du début à la fin.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
            // The Witcher 3 (rawgId: 3439)
            [
                'userId'       => 5, // David
                'username'     => 'DavidGG',
                'userAvatar'   => null,
                'gameId'       => 3439,
                'gameName'     => 'The Witcher 3: Wild Hunt',
                'rating'       => 5,
                'title'        => 'Le RPG ultime',
                'content'      => 'Une narration exceptionnelle, des quêtes secondaires meilleures que les quêtes principales d\'autres jeux. Incontournable.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
            [
                'userId'       => 10, // Ines
                'username'     => 'InesGames',
                'userAvatar'   => null,
                'gameId'       => 3439,
                'gameName'     => 'The Witcher 3: Wild Hunt',
                'rating'       => 5,
                'title'        => 'Geralt de Riv restera dans les mémoires',
                'content'      => 'Plus de 200 heures sur ce jeu et je n\'ai pas tout vu. La richesse du monde est incroyable.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
            // Elden Ring (rawgId: 278522)
            [
                'userId'       => 7, // Florian
                'username'     => 'FlorianX',
                'userAvatar'   => null,
                'gameId'       => 278522,
                'gameName'     => 'Elden Ring',
                'rating'       => 5,
                'title'        => 'FromSoftware au sommet de son art',
                'content'      => 'La liberté d\'exploration combinée à la difficulté signature de From. Un monde ouvert comme on n\'en avait jamais vu.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
            // Red Dead Redemption 2 (rawgId: 28)
            [
                'userId'       => 3, // Bob
                'username'     => 'BobPlays',
                'userAvatar'   => null,
                'gameId'       => 28,
                'gameName'     => 'Red Dead Redemption 2',
                'rating'       => 5,
                'title'        => 'Une œuvre d\'art interactive',
                'content'      => 'L\'histoire d\'Arthur Morgan est l\'une des plus belles du jeu vidéo. Rockstar s\'est surpassé.',
                'helpfulCount' => 0,
                'helpfulUsers' => [],
            ],
        ];

        foreach ($reviewsData as $data) {
            $review = new Review();
            $review->setUserId($data['userId']);
            $review->setUsername($data['username']);
            $review->setUserAvatar($data['userAvatar']);
            $review->setGameId($data['gameId']);
            $review->setGameName($data['gameName']);
            $review->setRating($data['rating']);
            $review->setTitle($data['title']);
            $review->setContent($data['content']);
            $review->setHelpfulCount($data['helpfulCount']);
            $review->setHelpfulUsers($data['helpfulUsers']);
            $review->setCreatedAt(new \DateTime('-' . rand(1, 180) . ' days'));
            $review->setUpdatedAt(new \DateTime());

            $this->dm->persist($review);
        }

        $this->dm->flush();

        echo "✅ " . count($reviewsData) . " reviews MongoDB insérées.\n";
    }
}