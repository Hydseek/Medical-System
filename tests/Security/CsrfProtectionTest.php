<?php

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CsrfProtectionTest extends KernelTestCase
{
    public function testCsrfTokenValidation()
    {
        self::bootKernel();

        // ensure a session is present for the Csrf token storage
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);

        /** @var CsrfTokenManagerInterface $manager */
        $manager = self::getContainer()->get('security.csrf.token_manager');

        $token = $manager->getToken('test-intention');
        $this->assertTrue($manager->isTokenValid($token));

        // invalid token should fail
        $invalid = new CsrfToken('test-intention', 'wrong');
        $this->assertFalse($manager->isTokenValid($invalid));
    }
}
