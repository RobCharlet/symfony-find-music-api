<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420152726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Discogs access token and  token nonce columns to AppUser table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD discogs_access_token BYTEA DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD discogs_refresh_token_nonce BYTEA DEFAULT NULL');
    }
}
