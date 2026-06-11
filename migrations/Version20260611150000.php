<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add app_follow table for follower/following relations (FEAT-P17).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_follow (follower_uuid UUID NOT NULL, followed_uuid UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (follower_uuid, followed_uuid))');
        $this->addSql('CREATE INDEX idx_follow_followed_uuid ON app_follow (followed_uuid)');
        $this->addSql('ALTER TABLE app_follow ADD CONSTRAINT fk_follow_follower_uuid FOREIGN KEY (follower_uuid) REFERENCES app_user (uuid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE app_follow ADD CONSTRAINT fk_follow_followed_uuid FOREIGN KEY (followed_uuid) REFERENCES app_user (uuid) ON DELETE CASCADE');
    }
}
