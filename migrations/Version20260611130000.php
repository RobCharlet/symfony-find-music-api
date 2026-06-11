<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add share_token to AppUser table for collection share links (FEAT-P19).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD share_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uq_share_token ON app_user (share_token)');
    }
}
