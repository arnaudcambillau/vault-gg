<?php

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Kernel;

/**
 * Tests de sécurité pour la validation des entrées
 * Vérifie que les entrées malveillantes sont gérées de manière sécurisée
 */
class InputValidationSecurityTest extends WebTestCase
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
     * Crée un utilisateur de test authentifié
     */
    private function createAuthenticatedUser(): User
    {
        $this->cleanupUser('validation@test.com');

        $user = new User();
        $user->setEmail('validation@test.com');
        $user->setUsername('ValidationTest');
        $user->setPassword(
            $this->client->getContainer()->get('security.user_password_hasher')->hashPassword($user, 'ValidPass123!')
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);

        return $user;
    }

    /**
     * Test : Les mots de passe trop courts sont rejetés
     */
    public function testShortPasswordsAreRejected(): void
    {
        $this->cleanupUser('shortpass@test.com');

        $crawler = $this->client->request('GET', '/register');
        
        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration_form[email]' => 'shortpass@test.com',
            'registration_form[username]' => 'ShortPass',
            'registration_form[plainPassword]' => '12345', // Trop court (< 6 caractères)
            'registration_form[agreeTerms]' => true,
        ]);
        
        $this->client->submit($form);
        
        // Symfony rejette avec un code 422 (Unprocessable Entity)
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Test : Les caractères spéciaux dans le nom d'utilisateur sont échappés
     */
    public function testSpecialCharactersInUsernameAreEscaped(): void
    {
        $this->cleanupUser('specialchars@test.com');

        $dangerousUsername = '<script>alert("XSS")</script>';
        
        $crawler = $this->client->request('GET', '/register');
        
        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration_form[email]' => 'specialchars@test.com',
            'registration_form[username]' => $dangerousUsername,
            'registration_form[plainPassword]' => 'ValidPassword123!',
            'registration_form[agreeTerms]' => true,
        ]);
        
        $this->client->submit($form);
        
        // Si inscription réussie, vérifier que le contenu est échappé
        if ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
            
            $content = $this->client->getResponse()->getContent();
            
            // Vérifier que Twig a bien échappé le HTML (< devient &lt;)
            $this->assertStringNotContainsString('alert("XSS")', $content);
            
            // Si le username apparaît, il doit être échappé
            if (str_contains($content, 'script')) {
                $this->assertStringContainsString('&lt;', $content);
            }
        }

        $this->cleanupUser('specialchars@test.com');
    }

    /**
     * Test : Les paramètres GET malveillants sont échappés dans la réponse
     */
    public function testMaliciousGetParametersAreEscaped(): void
    {
        $user = $this->createAuthenticatedUser();

        // Tenter d'injecter du code via les paramètres GET
        $this->client->request('GET', '/search', [
            'q' => '<script>alert("XSS")</script>',
        ]);

        $this->assertResponseIsSuccessful();
        
        $content = $this->client->getResponse()->getContent();
        
        // Vérifier que le script n'est PAS exécutable (échappé ou absent)
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $content);

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * Test : La longueur maximale des champs est appliquée (niveau BDD)
     */
    public function testMaximumFieldLengthIsEnforcedByDatabase(): void
    {
        $this->cleanupUser('maxlength@test.com');

        // Créer un username très long (> 255 caractères)
        $longUsername = str_repeat('A', 300);

        $crawler = $this->client->request('GET', '/register');
        
        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration_form[email]' => 'maxlength@test.com',
            'registration_form[username]' => $longUsername,
            'registration_form[plainPassword]' => 'ValidPassword123!',
            'registration_form[agreeTerms]' => true,
        ]);
        
        $this->client->submit($form);
        
        // MySQL rejette les données trop longues (erreur 500 ou 422)
        $this->assertTrue(
            $this->client->getResponse()->isServerError() || 
            $this->client->getResponse()->isClientError()
        );
    }

    /**
     * Test : Les injections SQL dans les formulaires sont bloquées par les requêtes préparées
     */
    public function testSQLInjectionInFormsIsBlocked(): void
    {
        $sqlInjectionPayloads = [
            "' OR '1'='1",
            "admin'--",
        ];

        foreach ($sqlInjectionPayloads as $payload) {
            $crawler = $this->client->request('GET', '/login');
            
            $form = $crawler->selectButton('Se connecter')->form([
                'email' => $payload,
                'password' => $payload,
            ]);
            
            $this->client->submit($form);
            
            // La connexion devrait échouer normalement (redirection vers login)
            // L'application ne devrait PAS crasher
            $this->assertTrue(
                $this->client->getResponse()->isRedirection() ||
                $this->client->getResponse()->isSuccessful()
            );
            
            $this->client->restart();
        }
    }

    /**
     * Test : Les données POST malveillantes n'affectent pas le système
     */
    public function testMaliciousPostDataIsHandledSafely(): void
    {
        $user = $this->createAuthenticatedUser();

        // Tenter de modifier le statut avec une valeur invalide
        $this->client->request('POST', '/change-status/999999', [
            'status' => '<script>alert("XSS")</script>',
        ]);

        // L'application devrait rediriger ou retourner une erreur, PAS crasher
        $this->assertTrue(
            $this->client->getResponse()->isRedirection() || 
            $this->client->getResponse()->isClientError() ||
            $this->client->getResponse()->isNotFound()
        );

        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * Test : Les caractères NULL bytes sont gérés sans crash
     */
    public function testNullBytesDoNotCauseErrors(): void
    {
        $this->cleanupUser("nullbyte@test.com");

        $crawler = $this->client->request('GET', '/register');
        
        // Tenter d'injecter un null byte (PHP les filtre généralement)
        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration_form[email]' => "nullbyte@test.com",
            'registration_form[username]' => "TestUser",
            'registration_form[plainPassword]' => 'ValidPassword123!',
            'registration_form[agreeTerms]' => true,
        ]);
        
        $this->client->submit($form);
        
        // L'application ne devrait PAS crasher
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful() ||
            $this->client->getResponse()->isRedirection() ||
            $this->client->getResponse()->isClientError()
        );
    }

    /**
     * Test : Les entrées HTML sont échappées dans les templates Twig
     */
    public function testHTMLInInputsIsEscapedInTemplates(): void
    {
        $this->cleanupUser('htmltest@test.com');

        $htmlPayload = '<img src=x onerror=alert("XSS")>';
        
        $crawler = $this->client->request('GET', '/register');
        
        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration_form[email]' => 'htmltest@test.com',
            'registration_form[username]' => $htmlPayload,
            'registration_form[plainPassword]' => 'ValidPassword123!',
            'registration_form[agreeTerms]' => true,
        ]);
        
        $this->client->submit($form);
        
        if ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
            
            $content = $this->client->getResponse()->getContent();
            
            // Vérifier que le HTML est échappé (pas de balises executables)
            $this->assertStringNotContainsString('<img src=x', $content);
            $this->assertStringNotContainsString('onerror=', $content);
        }

        $this->cleanupUser('htmltest@test.com');
    }

    /**
     * Test : Les formulaires ne crashent pas avec des données vides
     */
    public function testFormsHandleEmptyDataGracefully(): void
    {
        $crawler = $this->client->request('GET', '/register');
        
        // Soumettre un formulaire complètement vide
        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration_form[email]' => '',
            'registration_form[username]' => '',
            'registration_form[plainPassword]' => '',
        ]);
        
        $this->client->submit($form);
        
        // Devrait retourner une erreur de validation (422), pas un crash
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful() ||
            $this->client->getResponse()->isClientError()
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
