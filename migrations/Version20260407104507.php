<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407104507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Album PostgreSQL search_vector column.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE album ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (
                /* if title, artist or label is null, it will be replaced by an empty string */
                setweight(to_tsvector(\'simple\', coalesce(title, \'\') ), \'A\') ||
                setweight(to_tsvector(\'simple\', coalesce(artist, \'\') ), \'A\') ||
                setweight(to_tsvector(\'simple\', coalesce(label, \'\') ), \'B\')
            ) STORED;
        ');

        $this->addSql('
            CREATE INDEX search_vector_idx ON album USING GIN (search_vector);
        ');
    }
}
