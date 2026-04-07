<?php

namespace App\Collection\Infra\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class SearchAndSortIncompatible extends Constraint
{
    public string $message = 'Cannot be combined with search.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
