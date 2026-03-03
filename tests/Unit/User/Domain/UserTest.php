<?php

namespace App\Tests\Unit\User\Domain;

use App\User\Domain\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class UserTest extends TestCase
{
    #[Test]
    public function userIsCreatedWithProperties(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $user = new User(
            $uuid,
            'john@example.com',
            'hashed_password',
            ['ROLE_ADMIN'],
        );

        $this->assertSame($uuid, $user->getUuid());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertSame('hashed_password', $user->getPassword());
        $this->assertSame('john@example.com', $user->getUserIdentifier());
    }

    #[Test]
    public function getRolesAlwaysContainsRoleUser(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $userNoRoles = new User($uuid, 'a@b.com', 'pw', []);
        $this->assertContains('ROLE_USER', $userNoRoles->getRoles());

        $userAdmin = new User($uuid, 'a@b.com', 'pw', ['ROLE_ADMIN']);
        $roles = $userAdmin->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);

        $userDuplicate = new User($uuid, 'a@b.com', 'pw', ['ROLE_USER']);
        $this->assertCount(1, $userDuplicate->getRoles());
    }

    #[Test]
    public function updateUserWithoutPasswordThrowsTypeError(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $user = new User($uuid, 'old@example.com', 'pw', ['ROLE_USER']);

        // Documents bug: update() without password assigns null to string property
        $this->expectException(\TypeError::class);

        $user->update('new@example.com', ['ROLE_ADMIN']);
    }

    #[Test]
    public function updateUserWithPasswordChangesPassword(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $user = new User($uuid, 'a@b.com', 'old_pw', []);

        $user->update('a@b.com', [], 'new_pw');

        $this->assertSame('new_pw', $user->getPassword());
    }
}
