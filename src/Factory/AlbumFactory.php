<?php

namespace App\Factory;

use App\Collection\Domain\Album;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Album>
 */
final class AlbumFactory extends PersistentObjectFactory
{
    private const array ARTISTS = [
        'Pink Floyd',
        'The Beatles',
        'Radiohead',
        'Miles Davis',
        'Daft Punk',
        'Nirvana',
        'David Bowie',
        'Led Zeppelin',
        'The Rolling Stones',
        'Bob Dylan',
        'Kendrick Lamar',
        'Massive Attack',
        'Portishead',
        'Aphex Twin',
        'Boards of Canada',
    ];

    private const array FORMATS = [
        'Vinyl',
        'CD',
        'Digital',
        'Cassette',
        'LP',
        '12"',
        '7"',
    ];

    private const array GENRES = [
        'Rock',
        'Progressive Rock',
        'Jazz',
        'Electronic',
        'Hip-Hop',
        'Trip-Hop',
        'Alternative',
        'Blues',
        'Ambient',
        'Experimental',
        'Classic Rock',
        'Psychedelic',
    ];

    private const array TITLES = [
        'Dark Side of the Moon',
        'Abbey Road',
        'OK Computer',
        'Kind of Blue',
        'Discovery',
        'Nevermind',
        'The Rise and Fall of Ziggy Stardust',
        'IV',
        'Exile on Main St.',
        'Highway 61 Revisited',
        'To Pimp a Butterfly',
        'Mezzanine',
        'Dummy',
        'Selected Ambient Works 85-92',
        'Music Has the Right to Children',
    ];

    #[\Override]
    public static function class(): string
    {
        return Album::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'uuid' => UuidV7::v7(),
            'artist' => self::faker()->randomElement(self::ARTISTS),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'format' => self::faker()->randomElement(self::FORMATS),
            'ownerUuid' => Uuid::fromString(self::faker()->uuid()),
            'releaseYear' => self::faker()->randomNumber(),
            'title' => self::faker()->randomElement(self::TITLES),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'genre' => self::faker()->randomElement(self::GENRES),
            'label' => self::faker()->text(),
            'coverUrl' => 'https://example.com/covers/'.self::faker()->uuid().'.jpg',
            'isFavorite' => self::faker()->boolean(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Album $album): void {})
        ;
    }
}
