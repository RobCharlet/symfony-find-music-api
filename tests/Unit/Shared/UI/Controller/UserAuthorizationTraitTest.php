<?php

namespace App\Tests\Unit\Shared\UI\Controller;

use App\Shared\Domain\UuidAwareUserInterface;
use App\Shared\UI\Controller\UserAuthorizationTrait;
use App\Shared\UI\UserAuthorization;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\UuidV7;

class UserAuthorizationTraitTest extends TestCase
{
    #[Test]
    public function returnsUserAuthorizationForUuidAwareUser(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $user = $this->createStub(UuidAwareUserAndSecurityUser::class);
        $user->method('getUuid')->willReturn($uuid);

        $controller = new FakeController($user, isAdmin: false);
        $auth = $controller->exposeGetUserAuthorization();

        $this->assertInstanceOf(UserAuthorization::class, $auth);
        $this->assertTrue($auth->userUuid->equals($uuid));
        $this->assertFalse($auth->isAdmin);
    }

    #[Test]
    public function returnsIsAdminTrueWhenUserHasAdminRole(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $user = $this->createStub(UuidAwareUserAndSecurityUser::class);
        $user->method('getUuid')->willReturn($uuid);

        $controller = new FakeController($user, isAdmin: true);
        $auth = $controller->exposeGetUserAuthorization();

        $this->assertTrue($auth->isAdmin);
    }

    #[Test]
    public function throwsAccessDeniedWhenUserIsNull(): void
    {
        $controller = new FakeController(null, isAdmin: false);

        $this->expectException(AccessDeniedException::class);
        $controller->exposeGetUserAuthorization();
    }

    #[Test]
    public function throwsAccessDeniedWhenUserDoesNotImplementUuidAwareInterface(): void
    {
        $user = $this->createStub(UserInterface::class);

        $controller = new FakeController($user, isAdmin: false);

        $this->expectException(AccessDeniedException::class);
        $controller->exposeGetUserAuthorization();
    }
}

interface UuidAwareUserAndSecurityUser extends UserInterface, UuidAwareUserInterface
{
}

class FakeController
{
    use UserAuthorizationTrait;

    public function __construct(
        private ?UserInterface $user,
        private bool $isAdmin,
    ) {
    }

    public function exposeGetUserAuthorization(): UserAuthorization
    {
        return $this->getUserAuthorization();
    }

    private function getUser(): ?UserInterface
    {
        return $this->user;
    }

    private function isGranted(string $attribute): bool
    {
        return 'ROLE_ADMIN' === $attribute && $this->isAdmin;
    }

    private function createAccessDeniedException(string $message = 'Access Denied.'): AccessDeniedException
    {
        return new AccessDeniedException($message);
    }
}
