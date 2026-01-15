<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Kernel;

class HomeControllerTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }
    
    public function testHomePageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseRedirects('/login');
    }
    
    public function testAuthenticatedUserCanAccessHomePage(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(\App\Entity\User::class);
        $testUser = $userRepository->findOneByEmail('test@vault.gg');
        $client->loginUser($testUser);
        
        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Ma Bibliothèque');
    }
    
    public function testHomePageDisplaysStatistics(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(\App\Entity\User::class);
        $testUser = $userRepository->findOneByEmail('test@vault.gg');
        $client->loginUser($testUser);
        
        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }
    
    public function testSearchPageIsAccessible(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(\App\Entity\User::class);
        $testUser = $userRepository->findOneByEmail('test@vault.gg');
        $client->loginUser($testUser);
        
        $client->request('GET', '/search');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }
    
    public function testStatisticsPageIsAccessible(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(\App\Entity\User::class);
        $testUser = $userRepository->findOneByEmail('test@vault.gg');
        $client->loginUser($testUser);
        
        $client->request('GET', '/statistics');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Statistiques');
    }
    
    public function testProfilePageIsAccessible(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(\App\Entity\User::class);
        $testUser = $userRepository->findOneByEmail('test@vault.gg');
        $client->loginUser($testUser);
        
        $client->request('GET', '/profile');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mon Profil');
    }
}
