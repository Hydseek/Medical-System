<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RendezVousControllerTest extends WebTestCase
{
    public function testStep1RequiresLogin(): void
    {
        if (!class_exists(\Symfony\Component\BrowserKit\AbstractBrowser::class)) {
            $this->markTestSkipped('symfony/browser-kit is not installed in this environment.');
        }

        $client = static::createClient();
        $client->request('GET', '/rendez-vous');

        $this->assertTrue($client->getResponse()->isRedirect());
        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/connexion', $location);
    }
}
