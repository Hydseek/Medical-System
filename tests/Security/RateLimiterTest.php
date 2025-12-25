<?php

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimiterTest extends KernelTestCase
{
    public function testLoginLimiterBlocksAfterLimit()
    {
        self::bootKernel();
        /** @var RateLimiterFactory $factory */
        $factory = self::getContainer()->get('limiter.login_test');

        // create a limiter for an identifier (IP/user). Use a unique id to avoid cross-test state.
        $limiter = $factory->create(uniqid('test-'));

        // consume up to the limit
        for ($i = 0; $i < 5; $i++) {
            $res = $limiter->consume();
            $this->assertTrue($res->isAccepted(), 'Attempt '.$i.' should be accepted');
        }

        // next attempt should be denied
        $res = $limiter->consume();
        $this->assertFalse($res->isAccepted(), '6th attempt should be throttled');
    }
}
