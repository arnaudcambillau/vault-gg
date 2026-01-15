<?php
namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Kernel;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HomeControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;
    private $testUser;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Créer l'utilisateur de test
        $this->testUser = $this->createTestUser();
    }

    private function createTestUser(): User
    {
        // Chercher si l'utilisateur existe déjà
        $userRepository = $this->entityManager->getRepository(User::class);
        $existingUser = $userRepository->findOneBy(['email' => 'test@vault.gg']);

        // Si l'utilisateur existe, le supprimer d'abord
        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }

        // Créer le nouvel utilisateur
        $user = new User();
        $user->setEmail('test@vault.gg');
        $user->setUsername('TestUser');
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, 'Test1234!');
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        // Nettoyer l'utilisateur de test
        if ($this->testUser) {
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => 'test@vault.gg']);
            
            if ($user) {
                $this->entityManager->remove($user);
                $this->entityManager->flush();
            }
        }

        parent::tearDown();
    }

    public function testHomePageRequiresAuthentication(): void
    {
        // ✅ Utiliser $this->client au lieu de créer un nouveau client
        $this->client->request('GET', '/');
        $this->assertResponseRedirects('/login');
    }

    public function testAuthenticatedUserCanAccessHomePage(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Ma Bibliothèque');
    }

    public function testHomePageDisplaysStatistics(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }

    public function testSearchPageIsAccessible(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/search');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testStatisticsPageIsAccessible(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/statistics');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Statistiques');
    }

    public function testProfilePageIsAccessible(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/profile');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mon Profil');
    }
}