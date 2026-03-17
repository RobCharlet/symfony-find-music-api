<?php

namespace App\Factory;

use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use Symfony\Component\Uid\UuidV7;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ExternalReference>
 */
final class ExternalReferenceFactory extends PersistentObjectFactory
{
    private const array SPOTIFY_IDS = [
        '6mdiAmATAx73kdxrNrnlao',
        '0ETFjACtuP2ADo6LFhL6HN',
        '7dK54iZuOxXFarGhXwEXfF',
        '1weenld61qoidwYuZ1GESA',
        '2noRn2Aes5aoNVsU6iWThc',
        '1UsmQ3bpJTyK6ygoOOjG1r',
        '7nXJ5k4XgRj5OLg9m8V3zc',
        '6yiXkzHvC0OTmhfDQOEWtW',
        '4Gfnly5CzMJQqkUFfoHaP3',
        '3KfbEIOC7YIv90FIfNSZpo',
    ];

    private const array DISCOGS_IDS = [
        '249504',
        '368699',
        '977251',
        '1542267',
        '2345678',
        '8765432',
        '1223344',
        '5567788',
        '9887766',
        '4332211',
    ];

    private const array TIDAL_IDS = [
        '12345678',
        '23456789',
        '34567890',
        '45678901',
        '56789012',
        '67890123',
        '78901234',
        '89012345',
        '90123456',
        '11223344',
    ];

    private const array APPLE_MUSIC_IDS = [
        '1065973699',
        '1441164426',
        '1109686579',
        '1469577723',
        '1234567890',
        '9876543210',
        '1122334455',
        '5566778899',
        '9988776655',
        '4433221100',
    ];

    #[\Override]
    public static function class(): string
    {
        return ExternalReference::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        /** @var PlatformEnum $platform */
        $platform = self::faker()->randomElement(PlatformEnum::cases());

        $externalId = match ($platform) {
            PlatformEnum::Spotify => self::faker()->randomElement(self::SPOTIFY_IDS),
            PlatformEnum::Discogs => self::faker()->randomElement(self::DISCOGS_IDS),
            PlatformEnum::Tidal => self::faker()->randomElement(self::TIDAL_IDS),
            PlatformEnum::AppleMusic => self::faker()->randomElement(self::APPLE_MUSIC_IDS),
        };

        $metadata = match ($platform) {
            PlatformEnum::Spotify => [
                'popularity' => self::faker()->numberBetween(0, 100),
                'uri' => 'spotify:album:'.$externalId,
                'external_urls' => ['spotify' => 'https://open.spotify.com/album/'.$externalId],
            ],
            PlatformEnum::Discogs => [
                'resource_url' => 'https://api.discogs.com/releases/'.$externalId,
                'community' => [
                    'rating' => ['average' => self::faker()->randomFloat(2, 1, 5)],
                ],
            ],
            PlatformEnum::Tidal => [
                'url' => 'https://tidal.com/browse/album/'.$externalId,
                'audioQuality' => self::faker()->randomElement(['LOSSLESS', 'HI_RES', 'HIGH']),
            ],
            PlatformEnum::AppleMusic => [
                'url' => 'https://music.apple.com/album/'.$externalId,
                'contentRating' => self::faker()->optional()->passthrough('explicit'),
            ],
        };

        return [
            'uuid' => UuidV7::v7(),
            'album' => AlbumFactory::new(),
            'externalId' => $externalId,
            'platform' => $platform,
            'metadata' => $metadata,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(ExternalReference $externalReference): void {})
        ;
    }
}
