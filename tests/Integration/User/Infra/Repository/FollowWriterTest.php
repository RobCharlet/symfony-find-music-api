<?php

namespace App\Tests\Integration\User\Infra\Repository;

use App\Factory\SecurityUserFactory;
use App\User\Domain\Follow;
use App\User\Domain\Repository\FollowWriterInterface;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV7;

class FollowWriterTest extends KernelTestCase
{
    private const string MILES_UUID = '019c2e97-4f81-75c5-8eca-ec2ff86f7d56';
    private const string TRANE_UUID = '019c2e97-5a92-7c11-9f3a-1b2c3d4e5f60';

    #[Test]
    public function saveIsIdempotentOnDuplicateInsert(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $followWriter = $container->get(FollowWriterInterface::class);

        $follower = UuidV7::fromString(self::MILES_UUID);
        $followed = UuidV7::fromString(self::TRANE_UUID);

        SecurityUserFactory::createOne(['uuid' => $follower, 'email' => 'miles@example.com']);
        SecurityUserFactory::createOne(['uuid' => $followed, 'email' => 'trane@example.com']);

        // Simulates two concurrent requests that both passed the isFollowing() pre-check.
        $followWriter->save(Follow::create($follower, $followed));
        $followWriter->save(Follow::create($follower, $followed));

        $count = $container->get(Connection::class)->fetchOne(
            'SELECT COUNT(*) FROM app_follow WHERE follower_uuid = :follower AND followed_uuid = :followed',
            ['follower' => $follower->toRfc4122(), 'followed' => $followed->toRfc4122()]
        );

        $this->assertSame(1, (int) $count);
    }
}
