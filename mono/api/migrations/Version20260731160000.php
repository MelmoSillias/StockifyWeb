<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add soft-delete support on clients (deleted_at)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clients DROP deleted_at');
    }
}
