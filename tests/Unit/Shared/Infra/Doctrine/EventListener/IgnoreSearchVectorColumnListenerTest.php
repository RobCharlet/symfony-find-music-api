<?php

namespace App\Tests\Unit\Shared\Infra\Doctrine\EventListener;

use App\Shared\Infra\Doctrine\EventListener\IgnoreSearchVectorColumnListener;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class IgnoreSearchVectorColumnListenerTest extends TestCase
{
    private IgnoreSearchVectorColumnListener $listener;

    protected function setUp(): void
    {
        $this->listener = new IgnoreSearchVectorColumnListener();
    }

    private function makeArgs(Schema $schema): GenerateSchemaEventArgs
    {
        $args = $this->createStub(GenerateSchemaEventArgs::class);
        $args->method('getSchema')->willReturn($schema);

        return $args;
    }

    #[Test]
    public function doesNothingWhenAlbumTableDoesNotExist(): void
    {
        $schema = new Schema();

        $this->listener->postGenerateSchema($this->makeArgs($schema));

        $this->assertFalse($schema->hasTable('album'));
    }

    #[Test]
    public function addsSearchVectorColumnAndIndexWhenBothMissing(): void
    {
        $schema = new Schema();
        $schema->createTable('album');

        $this->listener->postGenerateSchema($this->makeArgs($schema));

        $table = $schema->getTable('album');
        $this->assertTrue($table->hasColumn('search_vector'));
        $this->assertTrue($table->hasIndex('search_vector_idx'));
    }

    #[Test]
    public function doesNotDuplicateColumnWhenAlreadyPresent(): void
    {
        $schema = new Schema();
        $table = $schema->createTable('album');
        $table->addColumn('search_vector', 'text', ['notnull' => false]);

        $this->listener->postGenerateSchema($this->makeArgs($schema));

        $this->assertCount(1, $table->getColumns());
    }

    #[Test]
    public function doesNotDuplicateIndexWhenAlreadyPresent(): void
    {
        $schema = new Schema();
        $table = $schema->createTable('album');
        $table->addColumn('search_vector', 'text', ['notnull' => false]);
        $table->addIndex(['search_vector'], 'search_vector_idx');

        $this->listener->postGenerateSchema($this->makeArgs($schema));

        $this->assertCount(1, $table->getIndexes());
    }
}
