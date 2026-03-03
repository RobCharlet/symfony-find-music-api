<?php

namespace App\Collection\Domain;

enum PlatformEnum: string
{
    case Discogs = 'discogs';
    case Spotify = 'spotify';
    case Tidal = 'tidal';
    case AppleMusic = 'apple_music';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
