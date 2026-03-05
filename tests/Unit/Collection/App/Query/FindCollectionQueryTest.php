<?php

namespace App\Tests\Unit\Collection\App\Query;

use App\Collection\App\Query\FindCollectionQuery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FindCollectionQueryTest extends TestCase
{
    #[Test]
    public function findCollectionQueryIsCreatedWithPageAndLimit()
    {
        $query = FindCollectionQuery::withPageAndLimit(1, 50);

        $this->assertSame(1, $query->page);
        $this->assertSame(50, $query->limit);
    }
}
