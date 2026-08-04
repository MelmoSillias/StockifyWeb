<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * V4 Fournisseur: commandes achat + fournisseur_id on stock_lots.
 */
final class Version20260802120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'V4 fournisseur: commandes_fournisseur, fournisseur_id on stock_lots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE commandes_fournisseur (reference VARCHAR(30) NOT NULL, fournisseur_id BINARY(16) NOT NULL, status VARCHAR(255) NOT NULL, total_amount NUMERIC(12, 2) NOT NULL, deposit_paid NUMERIC(12, 2) NOT NULL, confirmed_at DATETIME DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, expected_at DATE DEFAULT NULL, received_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_commande_fournisseur_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE commande_fournisseur_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_cost NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, commande_fournisseur_id BINARY(16) NOT NULL, INDEX IDX_commande_fournisseur_line (commande_fournisseur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE commande_fournisseur_lines ADD CONSTRAINT FK_commande_fournisseur_line FOREIGN KEY (commande_fournisseur_id) REFERENCES commandes_fournisseur (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE stock_lots ADD fournisseur_id BINARY(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_lots DROP fournisseur_id');
        $this->addSql('ALTER TABLE commande_fournisseur_lines DROP FOREIGN KEY FK_commande_fournisseur_line');
        $this->addSql('DROP TABLE commande_fournisseur_lines');
        $this->addSql('DROP TABLE commandes_fournisseur');
    }
}
