<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roles JSON column to users for ROLE_SUPER_ADMIN';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD roles JSON NOT NULL DEFAULT (JSON_ARRAY())');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP roles');
    }
}
