<?php

namespace App\Tests\Unit\User\App\Query;

use App\User\App\Query\FindUserQuery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindUserQueryTest extends TestCase
{
    #[Test]
    public function findUserQueryIsCreatedWithUuid(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $query = FindUserQuery::withUuid($uuid);

        $this->assertSame($uuid, $query->uuid);
    }
}
