<?php

namespace App\Collection\Infra\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class SearchAndSortIncompatibleValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        assert($constraint instanceof SearchAndSortIncompatible);

        if (null === $value->search) {
            return;
        }

        if (null !== $value->sortBy) {
            $this->context->buildViolation($constraint->message)
                ->atPath('sortBy')
                ->addViolation()
            ;
        }

        if (null !== $value->sortOrder) {
            $this->context->buildViolation($constraint->message)
                ->atPath('sortOrder')
                ->addViolation()
            ;
        }
    }
}
