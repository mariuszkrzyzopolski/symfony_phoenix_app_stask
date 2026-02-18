<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Tests\BaseWebTestCase;
use App\Entity\User;
use App\Entity\Photo;
use Doctrine\ORM\EntityManagerInterface;

class PhoenixTokenWorkflowTest extends BaseWebTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
    }

    public function testCompleteUserJourneyLoginSetTokenImportPhotos(): void
    {
        $client = static::createClient();
        $this->em = $client->getContainer()->get(EntityManagerInterface::class);
        
        $user = new User();
        $user->setUsername('workflowuser_' . uniqid());
        $user->setEmail('workflow_' . uniqid() . '@example.com');
        $user->setName('Workflow');
        $user->setLastName('User');
        
        $this->em->persist($user);
        $this->em->flush();

        $this->authenticateClient($client, $user);
        
        $client->request('GET', '/profile');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');

        $crawler = $client->getCrawler();
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'test_token_user2_def456']);
        $client->submit($form);
        
        $this->assertResponseRedirects('/profile');
        $client->followRedirect();
        
        $this->assertSelectorExists('.flash-message.success');
        $this->assertStringContainsString('photos imported successfully', $client->getCrawler()->filter('.flash-message.success')->text());

        $photos = $this->em->getRepository(Photo::class)->findBy(['user' => $user]);
        $this->assertNotEmpty($photos);
        
        $updatedUser = $this->em->getRepository(User::class)->find($user->getId());
        $this->assertEquals('test_token_user2_def456', $updatedUser->getPhoenixAccessToken());
    }

    public function testTokenPersistenceAcrossSessions(): void
    {
        $client = static::createClient();
        $this->em = $client->getContainer()->get(EntityManagerInterface::class);
        
        $user = new User();
        $user->setUsername('persistentuser_' . uniqid());
        $user->setEmail('persistent_' . uniqid() . '@example.com');
        $user->setPhoenixAccessToken('existing-persistent-token');
        
        $this->em->persist($user);
        $this->em->flush();

        $this->authenticateClient($client, $user);
        $client->request('GET', '/profile');
        
        $this->assertResponseIsSuccessful();
        $this->assertInputValueSame('phoenix_access_token', 'existing-persistent-token');
    }

    public function testErrorRecoveryScenarios(): void
    {
        $client = static::createClient();
        $this->em = $client->getContainer()->get(EntityManagerInterface::class);
        
        $user = new User();
        $user->setUsername('recoveryuser_' . uniqid());
        $user->setEmail('recovery_' . uniqid() . '@example.com');
        
        $this->em->persist($user);
        $this->em->flush();

        $this->authenticateClient($client, $user);

        $crawler = $client->request('GET', '/profile');
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'invalid-recovery-token']);
        $client->submit($form);
        
        $client->followRedirect();
        
        $this->assertSelectorExists('.flash-message.error');
        $errorText = $client->getCrawler()->filter('.flash-message.error')->text();
        $this->assertTrue(
            strpos($errorText, 'Invalid or expired') !== false || 
            strpos($errorText, 'Failed to connect') !== false
        );

        $crawler = $client->getCrawler();
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'test_token_user2_def456']);
        $client->submit($form);
        
        $client->followRedirect();
        
        $this->assertSelectorExists('.flash-message.success');
        $this->assertStringContainsString('photos imported successfully', $client->getCrawler()->filter('.flash-message.success')->text());
    }

    public function testTokenUpdateScenario(): void
    {
        $client = static::createClient();
        $this->em = $client->getContainer()->get(EntityManagerInterface::class);
        
        $user = new User();
        $user->setUsername('updateuser_' . uniqid());
        $user->setEmail('update_' . uniqid() . '@example.com');
        $user->setPhoenixAccessToken('old-token');
        
        $this->em->persist($user);
        $this->em->flush();

        $this->authenticateClient($client, $user);

        $crawler = $client->request('GET', '/profile');
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'test_token_user2_def456']);
        $client->submit($form);
        
        $client->followRedirect();
        
        $this->assertSelectorExists('.flash-message.success');
        $this->assertStringContainsString('photos imported successfully', $client->getCrawler()->filter('.flash-message.success')->text());

        $updatedUser = $this->em->getRepository(User::class)->find($user->getId());
        $this->assertEquals('test_token_user2_def456', $updatedUser->getPhoenixAccessToken());
    }

    public function testEmptyTokenSubmission(): void
    {
        $client = static::createClient();
        $this->em = $client->getContainer()->get(EntityManagerInterface::class);
        
        $user = new User();
        $user->setUsername('emptyuser_' . uniqid());
        $user->setEmail('empty_' . uniqid() . '@example.com');
        
        $this->em->persist($user);
        $this->em->flush();

        $this->authenticateClient($client, $user);

        $crawler = $client->request('GET', '/profile');
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => '']);
        $client->submit($form);
        
        $client->followRedirect();
        
        $this->assertSelectorExists('.flash-message.error');
        $this->assertStringContainsString('Phoenix API token is required', $client->getCrawler()->filter('.flash-message.error')->text());
    }

    public function testMultiplePhotoImports(): void
    {
        $client = static::createClient();
        $this->em = $client->getContainer()->get(EntityManagerInterface::class);
        
        $user = new User();
        $user->setUsername('multiuser_' . uniqid());
        $user->setEmail('multi_' . uniqid() . '@example.com');
        
        $this->em->persist($user);
        $this->em->flush();

        $this->authenticateClient($client, $user);

        $crawler = $client->request('GET', '/profile');
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'test_token_user2_def456']);
        $client->submit($form);
        $client->followRedirect();
        
        $this->assertSelectorExists('.flash-message.success');
        $this->assertStringContainsString('photos imported successfully', $client->getCrawler()->filter('.flash-message.success')->text());

        $photos = $this->em->getRepository(Photo::class)->findBy(['user' => $user]);
        $this->assertNotEmpty($photos);

        // Second import to check duplication
        $crawler = $client->getCrawler();
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'test_token_user2_def456']);
        $client->submit($form);
        $client->followRedirect();
        
        $this->assertSelectorExists('.flash-message.success');
        $this->assertStringContainsString('photos imported successfully', $client->getCrawler()->filter('.flash-message.success')->text());

        $photos = $this->em->getRepository(Photo::class)->findBy(['user' => $user]);
        $this->assertNotEmpty($photos);
    }
}
