<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Classe de base pour les tests nécessitant la base de données
 * 
 * Cette classe nettoie automatiquement la base de données entre chaque test
 * pour garantir l'isolation et la reproductibilité des tests.
 * 
 * ⚠️ IMPORTANT : Cette classe N'APPELLE PAS bootKernel() directement
 * pour éviter les conflits avec createClient() dans les classes enfants
 * 
 * Usage :
 * - Hériter de cette classe au lieu de WebTestCase
 * - L'EntityManager est disponible via $this->entityManager
 * - La base est automatiquement nettoyée avant chaque test
 * 
 * @author Arnaud - Vault.gg
 */
abstract class DatabaseTestCase extends WebTestCase
{
    protected EntityManagerInterface $entityManager;

    /**
     * Configure l'environnement de test avant chaque test
     * Nettoie automatiquement la base de données
     * 
     * ⚠️ Ne boot PAS le kernel ici - laisse les classes enfants le faire
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Utiliser getContainer() au lieu de bootKernel()
        // getContainer() boot le kernel automatiquement si nécessaire
        // et ne plante pas si le kernel est déjà booté
        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();
        
        // Nettoyer la base de données AVANT chaque test
        $this->cleanDatabase();
    }

    /**
     * Nettoie toutes les tables de la base de données de test
     * 
     * Utilise TRUNCATE pour vider les tables rapidement
     * Désactive temporairement les contraintes de clés étrangères
     */
    protected function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        
        try {
            // Désactiver les contraintes de clés étrangères (MySQL)
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            
            // Nettoyer les tables dans le bon ordre
            $connection->executeStatement('TRUNCATE TABLE user_game');
            $connection->executeStatement('TRUNCATE TABLE user');
            $connection->executeStatement('TRUNCATE TABLE game');
            $connection->executeStatement('TRUNCATE TABLE messenger_messages');
            
            // Réactiver les contraintes de clés étrangères
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            
        } catch (\Exception $e) {
            // En cas d'erreur, s'assurer de réactiver les contraintes
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            throw $e;
        }
    }

    /**
     * Nettoie l'environnement après chaque test
     * Ferme proprement l'EntityManager
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Fermer proprement l'entity manager pour éviter les fuites mémoire
        if (isset($this->entityManager)) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }

    /**
     * Helper pour créer et persister rapidement un utilisateur de test
     * 
     * @param string $email Email de l'utilisateur
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe en clair (sera hashé)
     * @param array $roles Rôles de l'utilisateur
     * @return \App\Entity\User L'utilisateur créé
     */
    protected function createTestUser(
        string $email = 'test@example.com',
        string $username = 'testuser',
        string $password = 'Test1234!',
        array $roles = ['ROLE_USER']
    ): \App\Entity\User {
        $passwordHasher = static::getContainer()
            ->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        
        $user = new \App\Entity\User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}