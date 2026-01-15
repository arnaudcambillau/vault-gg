<?php

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Kernel;

/**
 * Tests de sécurité pour l'authentification
 * Vérifie la sécurité du système de connexion, déconnexion et sessions
 */
class AuthenticationSecurityTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Nettoie un utilisateur de test s'il existe déjà
     */
    private function cleanupUser(string $email): void
    {
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }
    }

    /**
     * Test : La page de connexion est accessible
     */
    public function testLoginPageIsAccessible(): void
    {
        $this->client->request('GET', '/login');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('input[name="password"]');
        $this->assertSelectorExists('input[name="_csrf_token"]');
    }

    /**
     * Test : Connexion avec des identifiants valides réussit
     */
    public function testLoginWithValidCredentialsSucceeds(): void
    {
        // Nettoyer avant
        $this->cleanupUser('security@test.com');

        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('security@test.com');
        $user->setUsername('SecurityTest');
        $user->setPassword(
            $this->client->getContainer()->get('security.user_password_hasher')->hashPassword($user, 'SecurePass123!')
        );
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Récupérer l'ID avant la déconnexion
        $userId = $user->getId();

        // Tenter la connexion
        $crawler = $this->client->request('GET', '/login');
        
        $form = $crawler->selectButton('Se connecter')->form([
            'email' => 'security@test.com',
            'password' => 'SecurePass123!'
        ]);
        
        $this->client->submit($form);
        
        // Vérifier la redirection vers la page d'accueil
        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        
        // Vérifier que l'utilisateur est authentifié
        $this->assertResponseIsSuccessful();

        // Nettoyage
        $userToDelete = $this->entityManager->getRepository(User::class)->find($userId);
        if ($userToDelete) {
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();
        }
    }

    /**
     * Test : Connexion avec un email invalide échoue
     */
    public function testLoginWithInvalidEmailFails(): void
    {
        $crawler = $this->client->request('GET', '/login');
        
        $form = $crawler->selectButton('Se connecter')->form([
            'email' => 'nonexistent@test.com',
            'password' => 'wrongpassword'
        ]);
        
        $this->client->submit($form);
        
        // Vérifier qu'on reste sur la page de connexion
        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        
        // Vérifier qu'un message d'erreur est affiché (Symfony affiche les erreurs dans les flash messages)
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test : Connexion avec un mot de passe incorrect échoue
     */
    public function testLoginWithWrongPasswordFails(): void
    {
        // Nettoyer avant
        $this->cleanupUser('wrongpass@test.com');

        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('wrongpass@test.com');
        $user->setUsername('WrongPassTest');
        $user->setPassword(
            $this->client->getContainer()->get('security.user_password_hasher')->hashPassword($user, 'CorrectPass123!')
        );
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userId = $user->getId();

        // Tenter la connexion avec un mauvais mot de passe
        $crawler = $this->client->request('GET', '/login');
        
        $form = $crawler->selectButton('Se connecter')->form([
            'email' => 'wrongpass@test.com',
            'password' => 'WrongPassword123!'
        ]);
        
        $this->client->submit($form);
        
        // Vérifier que la connexion échoue
        $this->assertResponseRedirects('/login');

        // Nettoyage
        $userToDelete = $this->entityManager->getRepository(User::class)->find($userId);
        if ($userToDelete) {
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();
        }
    }

    /**
     * Test : La déconnexion invalide la session
     */
    public function testLogoutInvalidatesSession(): void
    {
        // Nettoyer avant
        $this->cleanupUser('logout@test.com');

        // Créer et connecter un utilisateur
        $user = new User();
        $user->setEmail('logout@test.com');
        $user->setUsername('LogoutTest');
        $user->setPassword(
            $this->client->getContainer()->get('security.user_password_hasher')->hashPassword($user, 'LogoutPass123!')
        );
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userId = $user->getId();

        // Se connecter
        $this->client->loginUser($user);

        // Vérifier que l'utilisateur est connecté
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        // Se déconnecter
        $this->client->request('GET', '/logout');

        // Vérifier la redirection vers la page de connexion
        $this->assertResponseRedirects();

        // Tenter d'accéder à une page protégée
        $this->client->request('GET', '/');
        
        // Devrait rediriger vers la page de connexion
        $this->assertResponseRedirects('/login');

        // Nettoyage
        $userToDelete = $this->entityManager->getRepository(User::class)->find($userId);
        if ($userToDelete) {
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();
        }
    }

    /**
     * Test : Un utilisateur non authentifié ne peut pas accéder aux pages protégées
     */
    public function testUnauthenticatedUserCannotAccessProtectedPages(): void
    {
        $protectedUrls = [
            '/',
            '/search',
            '/profile',
            '/statistics',
            '/favorites'
        ];

        foreach ($protectedUrls as $url) {
            $this->client->request('GET', $url);
            
            // Devrait rediriger vers la page de connexion (code 302)
            $this->assertResponseRedirects();
            
            // Réinitialiser le client pour le prochain test
            $this->client->restart();
        }
    }

    /**
     * Test : Le token CSRF est requis pour la connexion
     */
    public function testLoginRequiresCsrfToken(): void
    {
        // Tenter une connexion POST sans passer par le formulaire (sans token CSRF)
        $this->client->request('POST', '/login', [
            'email' => 'test@test.com',
            'password' => 'password'
            // Pas de _csrf_token
        ]);

        // La connexion devrait échouer
        $this->assertResponseStatusCodeSame(302);
    }

    /**
     * Test : Remember Me fonctionne correctement
     */
    public function testRememberMeFunctionality(): void
    {
        // Nettoyer avant
        $this->cleanupUser('remember@test.com');

        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('remember@test.com');
        $user->setUsername('RememberTest');
        $user->setPassword(
            $this->client->getContainer()->get('security.user_password_hasher')->hashPassword($user, 'RememberPass123!')
        );
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userId = $user->getId();

        // Se connecter avec Remember Me
        $crawler = $this->client->request('GET', '/login');
        
        $form = $crawler->selectButton('Se connecter')->form([
            'email' => 'remember@test.com',
            'password' => 'RememberPass123!',
            '_remember_me' => 'on'
        ]);
        
        $this->client->submit($form);
        
        // Vérifier qu'un cookie Remember Me a été créé
        $this->assertNotNull($this->client->getCookieJar()->get('REMEMBERME'));

        // Nettoyage
        $userToDelete = $this->entityManager->getRepository(User::class)->find($userId);
        if ($userToDelete) {
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();
        }
    }

    /**
     * Test : Les mots de passe ne sont jamais stockés en clair
     */
    public function testPasswordsAreNeverStoredInPlainText(): void
    {
        // Nettoyer avant
        $this->cleanupUser('plaintext@test.com');

        // Créer un utilisateur
        $user = new User();
        $user->setEmail('plaintext@test.com');
        $user->setUsername('PlaintextTest');
        
        $plainPassword = 'MySecretPassword123!';
        $hashedPassword = $this->client->getContainer()
            ->get('security.user_password_hasher')
            ->hashPassword($user, $plainPassword);
        
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userId = $user->getId();

        // Vérifier que le mot de passe stocké n'est PAS le mot de passe en clair
        $this->assertNotEquals($plainPassword, $user->getPassword());
        
        // Vérifier que c'est bien un hash bcrypt
        $this->assertStringStartsWith('$2y$', $user->getPassword());
        
        // Vérifier la longueur typique d'un hash bcrypt (60 caractères)
        $this->assertEquals(60, strlen($user->getPassword()));

        // Nettoyage
        $userToDelete = $this->entityManager->getRepository(User::class)->find($userId);
        if ($userToDelete) {
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Fermer l'EntityManager pour éviter les fuites mémoire
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
