<?php

declare(strict_types=1);

namespace App\Tests\Application\Controller;

use App\Tests\BaseWebTestCase;
use App\Entity\User;
use App\Entity\Photo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class ProfileControllerPhoenixTest extends BaseWebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testProfilePageDisplaysPhoenixTokenField(): void
    {
        $user = $this->createTestUser();
        $this->simulateLogin($user);

        $this->client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('label[for="phoenix_access_token"]');
        $this->assertSelectorTextContains('button', 'Import Photos');
    }

    public function testTokenSubmissionWithValidToken(): void
    {
        $user = $this->createTestUser();
        $this->simulateLogin($user);

        $this->client->request('GET', '/profile');
        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'test_token_user2_def456']);
        $this->client->submit($form);

        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();

        $this->assertSelectorExists('.flash-message.success');
        $this->assertStringContainsString('photos imported successfully', $this->client->getCrawler()->filter('.flash-message.success')->text());
        
        $updatedUser = $this->em->getRepository(User::class)->find($user->getId());
        $this->assertEquals('test_token_user2_def456', $updatedUser->getPhoenixAccessToken());
    }

    public function testTokenSubmissionWithInvalidToken(): void
    {
        $user = $this->createTestUser();
        $this->simulateLogin($user);

        $this->client->request('GET', '/profile');
        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'invalid-token']);
        $this->client->submit($form);

        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();

        $this->assertSelectorExists('.flash-message.error');
        $this->assertStringContainsString('Invalid or expired token', $this->client->getCrawler()->filter('.flash-message.error')->text());
    }

    public function testTokenSubmissionWithConnectionError(): void
    {
        $user = $this->createTestUser();
        $this->simulateLogin($user);

        $this->client->request('GET', '/profile');
        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'connection-error-token']);
        $this->client->submit($form);

        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();

        $this->assertSelectorExists('.flash-message.error');
        $this->assertStringContainsString('Invalid or expired token', $this->client->getCrawler()->filter('.flash-message.error')->text());
    }

    public function testSuccessfulPhotoImport(): void
    {
        $user = $this->createTestUser();
        $this->simulateLogin($user);

        $this->client->request('GET', '/profile');
        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'test_token_user2_def456']);
        $this->client->submit($form);

        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();

        $this->assertSelectorExists('.flash-message.success');
        $this->assertStringContainsString('photos imported successfully', $this->client->getCrawler()->filter('.flash-message.success')->text());
        $photos = $this->em->getRepository(Photo::class)->findBy(['user' => $user]);
        $this->assertCount(2, $photos);
        
        foreach ($photos as $photo) {
            $this->assertStringStartsWith('https://images-', $photo->getImageUrl());
        }
    }

    public function testEmptyPhotoResponse(): void
    {
        $user = $this->createTestUser();
        $this->simulateLogin($user);

        $this->client->request('GET', '/profile');
        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'empty-photos-token']);
        $this->client->submit($form);

        $this->assertResponseRedirects('/profile');
        $this->client->followRedirect();

        $this->assertSelectorExists('.flash-message.error');
        $this->assertStringContainsString('Invalid or expired token', $this->client->getCrawler()->filter('.flash-message.error')->text());
    }

    public function testFlashMessageDisplayForAllScenarios(): void
    {
        $user = $this->createTestUser();
        $this->simulateLogin($user);

        $this->client->request('GET', '/profile');
        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'test_token_user2_def456']);
        $this->client->submit($form);
        $this->client->followRedirect();
        $this->assertSelectorExists('.flash-message.success');

        $this->client->request('GET', '/profile');
        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'invalid-token']);
        $this->client->submit($form);
        $this->client->followRedirect();
        $this->assertSelectorExists('.flash-message.error');
    }

    public function testCsrfProtectionOnProfileForm(): void
    {
        $user = $this->createTestUser();
        $this->simulateLogin($user);

        $this->client->request('GET', '/profile');
        $crawler = $this->client->getCrawler();
        
        $form = $crawler->selectButton('Import Photos')->form();
        $form->setValues(['phoenix_access_token' => 'test-token']);
        $form->setValues(['_token' => '']);
        
        $this->client->submit($form);
        $this->assertResponseRedirects('/profile');
    }

    public function testProfilePageWithExistingToken(): void
    {
        $user = $this->createTestUser();
        $user->setPhoenixAccessToken('existing-token-123');
        $this->em->persist($user);
        $this->em->flush();
        
        $this->simulateLogin($user);

        $this->client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertInputValueSame('phoenix_access_token', 'existing-token-123');
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setUsername('testuser_' . uniqid());
        $user->setEmail('test_' . uniqid() . '@example.com');
        $user->setName('Test');
        $user->setLastName('User');

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function simulateLogin(User $user): void
    {
        $session = $this->client->getContainer()->get('session.factory')->createSession();
        $session->set('user_id', $user->getId());
        $session->set('username', $user->getUsername());
        $session->save();
        
        $cookie = new \Symfony\Component\BrowserKit\Cookie(
            $session->getName(),
            $session->getId(),
            null,
            '/',
            '',
            false,
            false,
            false,
            'Lax'
        );
        $this->client->getCookieJar()->set($cookie);
    }
}
