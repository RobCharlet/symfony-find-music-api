<?php

namespace App\Shared\Infra\Doctrine\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

// @see https://gist.github.com/vudaltsov/ec01012d3fe27c9eed59aa7fd9089cf7
// @see https://devlt.fr/blog/maitriser-les-migrations-avec-symfony-guide-pratique

/**
 * We need to prevent Doctrine dropping the PostgreSQL search_vector Album column and index.
 * So we add them after Doctrine generated the schema.
 */
#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
class IgnoreSearchVectorColumnListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        if (!$schema->hasTable('album')) {
            return;
        }

        $table = $schema->getTable('album');

        if (!$table->hasColumn('search_vector')) {
            $table->addColumn('search_vector', 'text', ['notnull' => false]);
        }

        if (!$table->hasIndex('search_vector_idx')) {
            $table->addIndex(['search_vector'], 'search_vector_idx');
        }

    }
}
