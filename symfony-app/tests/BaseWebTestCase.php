<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class BaseWebTestCase extends WebTestCase
{
    protected function authenticateClient($client, \App\Entity\User $user): void
    {
        $container = $client->getContainer();
        $session = $container->get('session.factory')->createSession();
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
        $client->getCookieJar()->set($cookie);
    }
}
