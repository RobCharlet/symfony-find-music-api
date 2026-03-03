<?php

namespace App\Tests\Unit\User\UI\RestNormalizer;

use App\User\Domain\User;
use App\User\UI\RestNormalizer\UserNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class UserNormalizerTest extends TestCase
{
    #[Test]
    public function normalizeReturnsOnlyUuidAndEmail(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $user = new User($uuid, 'john@example.com', 'secret', ['ROLE_ADMIN']);

        $normalizer = new UserNormalizer();
        $result = $normalizer->normalize($user);

        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('roles', $result);
        $this->assertSame('john@example.com', $result['email']);
    }

    #[Test]
    public function supportsNormalizationForUser(): void
    {
        $normalizer = new UserNormalizer();

        $user = new User(UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56'), 'a@b.com', 'pw');
        $this->assertTrue($normalizer->supportsNormalization($user));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }

    #[Test]
    public function getSupportedTypes(): void
    {
        $normalizer = new UserNormalizer();

        $types = $normalizer->getSupportedTypes(null);

        $this->assertSame([User::class => true], $types);
    }
}
