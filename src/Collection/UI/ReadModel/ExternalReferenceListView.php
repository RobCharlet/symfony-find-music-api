<?php

namespace App\Collection\UI\ReadModel;

use App\Collection\Domain\PaginatorInterface;
use App\Shared\App\DTO\PaginationDTO;
use Symfony\Component\JsonStreamer\Attribute\JsonStreamable;

/**
 * Paginated external reference list response streamed via JsonStreamer.
 */
#[JsonStreamable]
class ExternalReferenceListView
{
    /**
     * @param list<ExternalReferenceView> $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination,
    ) {
    }

    public static function fromPaginator(PaginatorInterface $paginator): self
    {
        $externalReferences = [];
        foreach ($paginator as $externalReference) {
            $externalReferences[] = ExternalReferenceView::fromExternalReference($externalReference);
        }

        return new self($externalReferences, PaginationDTO::fromPaginator($paginator));
    }
}
