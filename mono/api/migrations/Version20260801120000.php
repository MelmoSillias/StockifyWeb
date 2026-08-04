<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delivery_date on commandes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes ADD delivery_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commandes DROP delivery_date');
    }
}
