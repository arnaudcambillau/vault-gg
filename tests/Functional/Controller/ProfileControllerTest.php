<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Entity\Game;
use App\Entity\UserGame;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProfileControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;
    private $user;
    private $uploadDir;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $this->uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/uploads/avatars';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        
        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setUsername('testuser');
        $this->user->setPassword($this->passwordHasher->hashPassword($this->user, 'password123'));
        $this->user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        if (is_dir($this->uploadDir)) {
            $files = glob($this->uploadDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        $connection = $this->entityManager->getConnection();
        
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE user_game');
        $connection->executeStatement('TRUNCATE TABLE game');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('TRUNCATE TABLE messenger_messages');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function loginUser(): void
    {
        $this->client->loginUser($this->user);
    }

    private function createTestImage(string $filename): string
    {
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        $image = imagecreate(100, 100);
        $backgroundColor = imagecolorallocate($image, 255, 255, 255);
        imagepng($image, $filepath);
        imagedestroy($image);
        
        return $filepath;
    }

    public function testProfilePageDisplaysUserInfo(): void
    {
        $this->loginUser();
        
        $crawler = $this->client->request('GET', '/profile');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mon Profil');
        $this->assertSelectorTextContains('body', 'testuser');
        $this->assertSelectorTextContains('body', 'test@example.com');
    }

    public function testProfileDisplaysStatistics(): void
    {
        $this->loginUser();
        
        $game1 = new Game();
        $game1->setRawgId(200);
        $game1->setName('Test Game 1');
        $game1->setGenres(['Action']);
        
        $game2 = new Game();
        $game2->setRawgId(201);
        $game2->setName('Test Game 2');
        $game2->setGenres(['RPG']);
        
        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);
        
        $userGame1 = new UserGame();
        $userGame1->setUser($this->user);
        $userGame1->setGame($game1);
        $userGame1->setStatus('backlog');
        $userGame1->setIsFavorite(true);
        $userGame1->setAddedAt(new \DateTime());
        
        $userGame2 = new UserGame();
        $userGame2->setUser($this->user);
        $userGame2->setGame($game2);
        $userGame2->setStatus('completed');
        $userGame2->setIsFavorite(false);
        $userGame2->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame1);
        $this->entityManager->persist($userGame2);
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/profile');
        
        $this->assertResponseIsSuccessful();
        
        $this->assertStringContainsString('2', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('1', $this->client->getResponse()->getContent());
    }

    public function testUploadAvatarSuccess(): void
    {
        $this->loginUser();
        
        $testImagePath = $this->createTestImage('test_avatar.png');
        
        $uploadedFile = new UploadedFile(
            $testImagePath,
            'test_avatar.png',
            'image/png',
            null,
            true
        );
        
        $crawler = $this->client->request('GET', '/profile');
        
        $form = $crawler->selectButton('Uploader')->form();
        $form['avatar_form[avatar]']->upload($testImagePath);
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();
        
        $this->assertSelectorExists('[class*="bg-green-500"]');
        $this->assertSelectorTextContains('.p-4', 'Photo de profil mise à jour avec succès');
        
        $updatedUser = $this->entityManager->getRepository(User::class)->find($this->user->getId());
        $this->assertNotNull($updatedUser->getAvatar());
        
        $avatarPath = $this->uploadDir . '/' . $updatedUser->getAvatar();
        $this->assertFileExists($avatarPath);
        
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }
    }

    public function testUploadAvatarWithInvalidFormat(): void
    {
        $this->loginUser();
        
        $testFilePath = sys_get_temp_dir() . '/test.txt';
        file_put_contents($testFilePath, 'This is not an image');
        
        $crawler = $this->client->request('GET', '/profile');
        
        $form = $crawler->selectButton('Uploader')->form();
        $form['avatar_form[avatar]']->upload($testFilePath);
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();
        
        $this->assertSelectorExists('[class*="bg-red-500"]');
        
        if (file_exists($testFilePath)) {
            unlink($testFilePath);
        }
    }

    public function testUploadAvatarWithLargeFile(): void
    {
        $this->loginUser();
        
        // Créer un fichier de 2.5 Mo (dépasse la limite de 2 Mo)
        $largeImagePath = sys_get_temp_dir() . '/large_image.jpg';
        $fileContent = str_repeat('x', (int)(2.5 * 1024 * 1024)); // 2.5 Mo
        file_put_contents($largeImagePath, $fileContent);
        
        // Vérifier que le fichier fait bien plus de 2 Mo
        $this->assertGreaterThan(2097152, filesize($largeImagePath), 
            'Le fichier de test doit faire plus de 2 Mo. Taille actuelle : ' . filesize($largeImagePath));
        
        $crawler = $this->client->request('GET', '/profile');
        
        $form = $crawler->selectButton('Uploader')->form();
        $form['avatar_form[avatar]']->upload($largeImagePath);
        
        $this->client->submit($form);
        
        // Vérifier la redirection
        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();
        
        // Vérifier le message d'erreur
        $this->assertSelectorExists('[class*="bg-red-500"]');
        $this->assertSelectorTextContains('.p-4', 'doit pas dépasser 2 Mo');
        
        // Nettoyer le fichier temporaire
        if (file_exists($largeImagePath)) {
            unlink($largeImagePath);
        }
    }

    public function testReplaceExistingAvatar(): void
    {
        $this->loginUser();
        
        $oldAvatarPath = $this->uploadDir . '/old_avatar.png';
        $oldImage = imagecreate(100, 100);
        imagepng($oldImage, $oldAvatarPath);
        imagedestroy($oldImage);
        
        $this->user->setAvatar('old_avatar.png');
        $this->entityManager->flush();
        
        $this->assertFileExists($oldAvatarPath);
        
        $newTestImagePath = $this->createTestImage('new_avatar.png');
        
        $crawler = $this->client->request('GET', '/profile');
        
        $form = $crawler->selectButton('Uploader')->form();
        $form['avatar_form[avatar]']->upload($newTestImagePath);
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();
        
        $this->assertSelectorExists('[class*="bg-green-500"]');
        
        $this->assertFileDoesNotExist($oldAvatarPath);
        
        $updatedUser = $this->entityManager->getRepository(User::class)->find($this->user->getId());
        $this->assertNotNull($updatedUser->getAvatar());
        $this->assertNotEquals('old_avatar.png', $updatedUser->getAvatar());
        
        $newAvatarPath = $this->uploadDir . '/' . $updatedUser->getAvatar();
        $this->assertFileExists($newAvatarPath);
        
        if (file_exists($newTestImagePath)) {
            unlink($newTestImagePath);
        }
    }

    public function testProfileDisplaysRecentGames(): void
    {
        $this->loginUser();
        
        $game = new Game();
        $game->setRawgId(202);
        $game->setName('Recent Game');
        $game->setGenres(['Action']);
        
        $this->entityManager->persist($game);
        
        $userGame = new UserGame();
        $userGame->setUser($this->user);
        $userGame->setGame($game);
        $userGame->setStatus('in_progress');
        $userGame->setIsFavorite(false);
        $userGame->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame);
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/profile');
        
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Recent Game', $this->client->getResponse()->getContent());
    }

    public function testProfileDisplaysCompletionRate(): void
    {
        $this->loginUser();
        
        $game1 = new Game();
        $game1->setRawgId(203);
        $game1->setName('Completed Game');
        $game1->setGenres(['Action']);
        
        $game2 = new Game();
        $game2->setRawgId(204);
        $game2->setName('Backlog Game');
        $game2->setGenres(['RPG']);
        
        $this->entityManager->persist($game1);
        $this->entityManager->persist($game2);
        
        $userGame1 = new UserGame();
        $userGame1->setUser($this->user);
        $userGame1->setGame($game1);
        $userGame1->setStatus('completed');
        $userGame1->setIsFavorite(false);
        $userGame1->setAddedAt(new \DateTime());
        
        $userGame2 = new UserGame();
        $userGame2->setUser($this->user);
        $userGame2->setGame($game2);
        $userGame2->setStatus('backlog');
        $userGame2->setIsFavorite(false);
        $userGame2->setAddedAt(new \DateTime());
        
        $this->entityManager->persist($userGame1);
        $this->entityManager->persist($userGame2);
        $this->entityManager->flush();
        
        $crawler = $this->client->request('GET', '/profile');
        
        $this->assertResponseIsSuccessful();
        
        $this->assertStringContainsString('50%', $this->client->getResponse()->getContent());
    }
}