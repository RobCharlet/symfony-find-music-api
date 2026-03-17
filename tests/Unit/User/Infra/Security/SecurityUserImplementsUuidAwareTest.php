<?php

namespace App\Tests\Unit\User\Infra\Security;

use App\Shared\Domain\UuidAwareUserInterface;
use App\User\Infra\Security\SecurityUser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class SecurityUserImplementsUuidAwareTest extends TestCase
{
    #[Test]
    public function implementsUuidAwareUserInterface(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $securityUser = new SecurityUser($uuid, 'miles@davis.com', 'hashed', ['ROLE_USER']);

        $this->assertInstanceOf(UuidAwareUserInterface::class, $securityUser);
        $this->assertTrue($securityUser->getUuid()->equals($uuid));
    }
}
