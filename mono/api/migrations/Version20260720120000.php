<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * V2 Commerce schema: clients, commerce (ventes/commandes), facturation, paiements.
 * Cross-module references are stored as plain UUID columns (no foreign keys)
 * to keep bounded contexts decoupled.
 */
final class Version20260720120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'V2 commerce: clients, ventes, commandes, factures, paiements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE clients (name VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, credit_limit NUMERIC(12, 2) DEFAULT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE ventes (reference VARCHAR(30) NOT NULL, client_id BINARY(16) DEFAULT NULL, anonymous_info VARCHAR(255) DEFAULT NULL, total_amount NUMERIC(12, 2) NOT NULL, created_at DATETIME NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_vente_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vente_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, vente_id BINARY(16) NOT NULL, INDEX IDX_vente_line_vente (vente_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE commandes (reference VARCHAR(30) NOT NULL, client_id BINARY(16) DEFAULT NULL, anonymous_info VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, total_amount NUMERIC(12, 2) NOT NULL, deposit_received NUMERIC(12, 2) NOT NULL, confirmed_at DATETIME DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_commande_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, commande_id BINARY(16) NOT NULL, INDEX IDX_commande_line_commande (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE factures (numero VARCHAR(30) NOT NULL, vente_id BINARY(16) DEFAULT NULL, commande_id BINARY(16) DEFAULT NULL, source_reference VARCHAR(30) NOT NULL, client_id BINARY(16) DEFAULT NULL, anonymous_info VARCHAR(255) DEFAULT NULL, total_amount NUMERIC(12, 2) NOT NULL, is_creance TINYINT(1) NOT NULL, is_creance_finalized TINYINT(1) NOT NULL, credit_closed_at DATETIME DEFAULT NULL, issued_at DATETIME NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_facture_numero (numero), INDEX idx_facture_creance (is_creance, client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE facture_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, facture_id BINARY(16) NOT NULL, INDEX IDX_facture_line_facture (facture_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE paiements (reference VARCHAR(30) NOT NULL, facture_id BINARY(16) DEFAULT NULL, commande_id BINARY(16) DEFAULT NULL, amount NUMERIC(12, 2) NOT NULL, method VARCHAR(255) NOT NULL, paid_at DATETIME NOT NULL, cancelled_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_paiement_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE vente_lines ADD CONSTRAINT FK_vente_line_vente FOREIGN KEY (vente_id) REFERENCES ventes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_lines ADD CONSTRAINT FK_commande_line_commande FOREIGN KEY (commande_id) REFERENCES commandes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture_lines ADD CONSTRAINT FK_facture_line_facture FOREIGN KEY (facture_id) REFERENCES factures (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vente_lines DROP FOREIGN KEY FK_vente_line_vente');
        $this->addSql('ALTER TABLE commande_lines DROP FOREIGN KEY FK_commande_line_commande');
        $this->addSql('ALTER TABLE facture_lines DROP FOREIGN KEY FK_facture_line_facture');
        $this->addSql('DROP TABLE paiements');
        $this->addSql('DROP TABLE facture_lines');
        $this->addSql('DROP TABLE factures');
        $this->addSql('DROP TABLE commande_lines');
        $this->addSql('DROP TABLE commandes');
        $this->addSql('DROP TABLE vente_lines');
        $this->addSql('DROP TABLE ventes');
        $this->addSql('DROP TABLE clients');
    }
}
