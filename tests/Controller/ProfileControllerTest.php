<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProfileControllerTest extends WebTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile');

        self::assertResponseRedirects('/login');
    }

    public function testOrdersRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile/orders');

        self::assertResponseRedirects('/login');
    }
}
