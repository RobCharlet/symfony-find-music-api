<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407100010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return ' Remove Album is_favorite column default value.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album ALTER is_favorite DROP DEFAULT');
    }
}
