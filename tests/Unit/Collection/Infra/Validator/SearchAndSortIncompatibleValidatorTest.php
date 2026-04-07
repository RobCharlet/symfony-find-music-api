<?php

namespace App\Tests\Unit\Collection\Infra\Validator;

use App\Collection\App\Query\FindAlbumsByOwnerWithPaginationQuery;
use App\Collection\Infra\Validator\SearchAndSortIncompatible;
use App\Collection\Infra\Validator\SearchAndSortIncompatibleValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

class SearchAndSortIncompatibleValidatorTest extends TestCase
{
    private function buildQuery(?string $search, ?string $sortBy, ?string $sortOrder = null): object
    {
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $requesterUuid = UuidV7::fromString('019cba11-cd78-7ffa-8133-66be4c2ac39a');

        return FindAlbumsByOwnerWithPaginationQuery::withOwnerUuid(
            ownerUuid: $ownerUuid,
            requesterUuid: $requesterUuid,
            isAdmin: false,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
            search: $search,
        );
    }

    #[Test]
    public function noViolationWhenSearchIsNull(): void
    {
        $validator = new SearchAndSortIncompatibleValidator();
        $context = $this->createMock(ExecutionContext::class);
        $context->expects($this->never())->method('buildViolation');

        $validator->initialize($context);
        $validator->validate($this->buildQuery(null, 'title'), new SearchAndSortIncompatible());
    }

    #[Test]
    public function noViolationWhenSortByIsNull(): void
    {
        $validator = new SearchAndSortIncompatibleValidator();
        $context = $this->createMock(ExecutionContext::class);
        $context->expects($this->never())->method('buildViolation');

        $validator->initialize($context);
        $validator->validate($this->buildQuery('coltrane', null), new SearchAndSortIncompatible());
    }

    #[Test]
    public function noViolationWhenSortOrderIsNull(): void
    {
        $validator = new SearchAndSortIncompatibleValidator();
        $context = $this->createMock(ExecutionContext::class);
        $context->expects($this->never())->method('buildViolation');

        $validator->initialize($context);
        $validator->validate($this->buildQuery('coltrane', null), new SearchAndSortIncompatible());
    }

    #[Test]
    public function addViolationWhenBothSearchAndSortByAreSet(): void
    {
        $validator = new SearchAndSortIncompatibleValidator();
        $constraint = new SearchAndSortIncompatible();

        $violationBuilder = $this->createMock(ConstraintViolationBuilder::class);
        $violationBuilder->expects($this->once())->method('atPath')->with('sortBy')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContext::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);

        $validator->initialize($context);
        $validator->validate($this->buildQuery('coltrane', 'title'), $constraint);
    }

    #[Test]
    public function addViolationWhenBothSearchAndSortOrderAreSet(): void
    {
        $validator = new SearchAndSortIncompatibleValidator();
        $constraint = new SearchAndSortIncompatible();

        $violationBuilder = $this->createMock(ConstraintViolationBuilder::class);
        $violationBuilder->expects($this->once())->method('atPath')->with('sortOrder')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContext::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);

        $validator->initialize($context);
        $validator->validate($this->buildQuery('coltrane', null, 'DESC'), $constraint);
    }
}
