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
        $this->dm->getDocumentCollection(Review::class)->deleteMany([]);

        $getGame = function(int $rawgId): array {
            foreach (GamesData::GAMES as $game) {
                if ($game['rawgId'] === $rawgId) {
                    return $game;
                }
            }
            throw new \Exception("rawgId $rawgId introuvable dans GamesData !");
        };

        $reviewsData = [
            // GTA V (3498)
            [
                'userId' => 2, 'username' => 'AliceGamer',
                'rawgId' => 3498, 'rating' => 5,
                'title'   => 'Un chef-d\'œuvre absolu',
                'content' => 'Ce jeu est incroyable, des heures et des heures de gameplay. Le mode histoire est captivant et le online est infini.',
            ],
            [
                'userId' => 5, 'username' => 'DavidGG',
                'rawgId' => 3498, 'rating' => 4,
                'title'   => 'Toujours aussi bon 10 ans après',
                'content' => 'Rockstar a vraiment livré un jeu intemporel. Quelques mécaniques vieillissantes mais le contenu est colossal.',
            ],
            // Portal 2 (4200)
            [
                'userId' => 2, 'username' => 'AliceGamer',
                'rawgId' => 4200, 'rating' => 5,
                'title'   => 'Le meilleur puzzle-game de tous les temps',
                'content' => 'La narration, les mécaniques, l\'humour... Portal 2 est parfait de bout en bout. Le co-op est une expérience unique.',
            ],
            [
                'userId' => 7, 'username' => 'FlorianX',
                'rawgId' => 4200, 'rating' => 5,
                'title'   => 'GlaDOS est le meilleur personnage du jeu vidéo',
                'content' => 'L\'écriture est brillante, les puzzles sont parfaitement dosés. Un jeu que tout le monde devrait faire.',
            ],
            // Skyrim (5679)
            [
                'userId' => 3, 'username' => 'BobPlays',
                'rawgId' => 5679, 'rating' => 4,
                'title'   => 'Un monde ouvert épique',
                'content' => 'Skyrim reste une référence en termes de monde ouvert. Quelques bugs certes, mais l\'immersion est totale.',
            ],
            // Cyberpunk 2077 (41494)
            [
                'userId' => 4, 'username' => 'CharlieX',
                'rawgId' => 41494, 'rating' => 3,
                'title'   => 'Bon jeu, lancement catastrophique',
                'content' => 'Le lore est fascinant et Night City est magnifique. Maintenant bien patchés, les bugs ont largement disparu.',
            ],
            [
                'userId' => 8, 'username' => 'HugoVault',
                'rawgId' => 41494, 'rating' => 5,
                'title'   => 'Après les patches, un chef-d\'œuvre',
                'content' => 'J\'ai attendu 2 ans pour y jouer et je n\'ai pas été déçu. L\'histoire de V est captivante du début à la fin.',
            ],
            // The Witcher 3 (3328) ✅
            [
                'userId' => 5, 'username' => 'DavidGG',
                'rawgId' => 3328, 'rating' => 5,
                'title'   => 'Le RPG ultime',
                'content' => 'Une narration exceptionnelle, des quêtes secondaires meilleures que les quêtes principales d\'autres jeux. Incontournable.',
            ],
            [
                'userId' => 10, 'username' => 'InesGames',
                'rawgId' => 3328, 'rating' => 5,
                'title'   => 'Geralt de Riv restera dans les mémoires',
                'content' => 'Plus de 200 heures sur ce jeu et je n\'ai pas tout vu. La richesse du monde est incroyable.',
            ],
            // Elden Ring (326243) ✅
            [
                'userId' => 7, 'username' => 'FlorianX',
                'rawgId' => 326243, 'rating' => 5,
                'title'   => 'FromSoftware au sommet de son art',
                'content' => 'La liberté d\'exploration combinée à la difficulté signature de From. Un monde ouvert comme on n\'en avait jamais vu.',
            ],
            // Red Dead Redemption 2 (28)
            [
                'userId' => 3, 'username' => 'BobPlays',
                'rawgId' => 28, 'rating' => 5,
                'title'   => 'Une œuvre d\'art interactive',
                'content' => 'L\'histoire d\'Arthur Morgan est l\'une des plus belles du jeu vidéo. Rockstar s\'est surpassé.',
            ],
        ];

        foreach ($reviewsData as $data) {
            $gameData = $getGame($data['rawgId']);

            $review = new Review();
            $review->setUserId($data['userId']);
            $review->setUsername($data['username']);
            $review->setUserAvatar(null);
            $review->setGameId($data['rawgId']);
            $review->setGameName($gameData['name']);
            $review->setRating($data['rating']);
            $review->setTitle($data['title']);
            $review->setContent($data['content']);
            $review->setHelpfulCount(0);
            $review->setHelpfulUsers([]);
            $review->setCreatedAt(new \DateTime('-' . rand(1, 180) . ' days'));
            $review->setUpdatedAt(new \DateTime());

            $this->dm->persist($review);
        }

        $this->dm->flush();

        echo "✅ " . count($reviewsData) . " reviews MongoDB insérées.\n";
    }
}