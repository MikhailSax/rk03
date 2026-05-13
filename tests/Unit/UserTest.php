<?php

namespace App\Tests\Unit;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testEmailAndPhoneNormalization(): void
    {
        $user = new User();
        $user->setEmail('  USER@Example.COM  ');
        $user->setPhone('8 (999) 123-45-67');

        self::assertSame('user@example.com', $user->getEmail());
        self::assertSame('+79991234567', $user->getPhone());
    }

    public function testRolesAlwaysContainRoleUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testSetPhoneCanBeNull(): void
    {
        $user = new User();
        $user->setPhone(null);

        self::assertNull($user->getPhone());
    }

    public function testYandexIdSupportsLargeValues(): void
    {
        $user = new User();
        $user->setYandexId('2224529849');

        self::assertSame('2224529849', $user->getYandexId());
    }
}
