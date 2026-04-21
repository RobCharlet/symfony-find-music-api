<?php

namespace App\Collection\Domain;

enum SortDirectionEnum: string
{
    case Asc = 'ASC';
    case Desc = 'DESC';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
