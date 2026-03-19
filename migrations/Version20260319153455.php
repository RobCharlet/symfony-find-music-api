<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319153455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make Album is_favorite column NOT NULL with default false.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE album SET is_favorite = false WHERE is_favorite IS NULL');
        $this->addSql('ALTER TABLE album ALTER COLUMN is_favorite SET DEFAULT false');
        $this->addSql('ALTER TABLE album ALTER COLUMN is_favorite SET NOT NULL');
    }
}
