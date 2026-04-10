<?php

namespace App\Collection\App\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

// @see https://symfony.com/doc/current/controller.html#mapping-the-whole-query-string
final readonly class CollectionFilters
{
    public function __construct(
        #[OA\Property(description: 'Sort field', enum: ['title', 'artist', 'genre', 'releaseYear', 'format', 'label'], example: 'title')]
        #[Assert\NotBlank(
            message: 'The sort_by parameter cannot be an empty string. Omit it if not used.',
            allowNull: true
        )]
        public ?string $sortBy = null,
        #[OA\Property(description: 'Sort order', enum: ['ASC', 'DESC'], example: 'DESC')]
        #[Assert\Choice(choices: ['ASC', 'DESC'], message: 'The sort_order parameter must be either "ASC" or "DESC".')]
        public ?string $sortOrder = null,
        #[OA\Property(description: 'Filter by favorite status', example: true)]
        public ?bool $isFavorite = null,
        #[OA\Property(description: 'Filter by genre', example: 'electronica')]
        #[Assert\NotBlank(
            message: 'The genre parameter cannot be an empty string. Omit it if not used.',
            allowNull: true
        )]
        public ?string $genre = null,
        #[OA\Property(description: 'Full-text search on title, artist and label using PostgreSQL websearch_to_tsquery. Supports natural language operators: quotes for exact phrases ("blue train"), minus to exclude (-live), OR for alternatives (vinyl OR cd). Results are ranked by relevance when search is active.', example: 'coltrane')]
        #[Assert\NotBlank(
            message: 'The search parameter cannot be an empty string. Omit it if not used.',
            allowNull: true
        )]
        public ?string $search = null,
        #[OA\Property(description: 'Filter by artist', example: 'John Coltrane')]
        #[Assert\NotBlank(
            message: 'The artist parameter cannot be an empty string. Omit it if not used.',
            allowNull: true
        )]
        public ?string $artist = null,
        #[OA\Property(description: 'Filter by format', example: 'CD')]
        #[Assert\NotBlank(
            message: 'The format parameter cannot be an empty string. Omit it if not used.',
            allowNull: true
        )]
        public ?string $format = null,
        #[OA\Property(description: 'Filter by label', example: 'Impulse!')]
        #[Assert\NotBlank(
            message: 'The label parameter cannot be an empty string. Omit it if not used.',
            allowNull: true
        )]
        public ?string $label = null,
        #[OA\Property(description: 'Filter from year (inclusive)', example: 1956)]
        public ?int $yearFrom = null,
        #[OA\Property(description: 'Filter to year (inclusive)', example: 2018)]
        public ?int $yearTo = null,
        #[OA\Property(description: 'Page number', example: 1)]
        #[Assert\GreaterThanOrEqual(1)]
        public int $page = 1,
        #[OA\Property(description: 'Resources per page', example: 50)]
        #[Assert\GreaterThanOrEqual(1)]
        public int $limit = 50,
    ) {
    }
}
