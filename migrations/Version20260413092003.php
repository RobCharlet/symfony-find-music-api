<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413092003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add rating and personalNote columns to Album table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album ADD rating INT DEFAULT NULL');
        $this->addSql('ALTER TABLE album ADD personal_note TEXT DEFAULT NULL');
    }
}
