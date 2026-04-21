<?php

namespace App\Collection\Domain;

enum SortByEnum: string
{
    case Title = 'title';
    case Artist = 'artist';
    case Genre = 'genre';
    case ReleaseYear = 'releaseYear';
    case Format = 'format';
    case Label = 'label';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
