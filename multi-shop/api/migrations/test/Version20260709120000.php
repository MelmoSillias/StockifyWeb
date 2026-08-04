<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure users.roles JSON column exists';
    }

    public function up(Schema $schema): void
    {
        // No-op for mono (roles already in initial migration).
    }

    public function down(Schema $schema): void
    {
    }
}
