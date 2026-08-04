<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Commerce: devis and devis_lines tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE devis (reference VARCHAR(30) NOT NULL, client_id BINARY(16) DEFAULT NULL, anonymous_info VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, total_amount NUMERIC(12, 2) NOT NULL, created_at DATETIME NOT NULL, valid_until DATE DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, converted_vente_id BINARY(16) DEFAULT NULL, converted_commande_id BINARY(16) DEFAULT NULL, id BINARY(16) NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_devis_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE devis_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, devis_id BINARY(16) NOT NULL, INDEX IDX_devis_line_devis (devis_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE devis_lines ADD CONSTRAINT FK_devis_line_devis FOREIGN KEY (devis_id) REFERENCES devis (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE devis_lines DROP FOREIGN KEY FK_devis_line_devis');
        $this->addSql('DROP TABLE devis_lines');
        $this->addSql('DROP TABLE devis');
    }
}
