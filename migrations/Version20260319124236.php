<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319124236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Album is_favorite column.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album ADD is_favorite BOOLEAN DEFAULT NULL');
    }
}
