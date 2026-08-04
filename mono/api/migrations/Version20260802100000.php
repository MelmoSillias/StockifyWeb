<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * V4 Fournisseur: fournisseurs, dettes, paiements fournisseur.
 * Cross-module references are plain UUID columns (no FK).
 */
final class Version20260802100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'V4 fournisseur: fournisseurs, dettes_fournisseur, paiements_fournisseur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE fournisseurs (name VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE dettes_fournisseur (reference VARCHAR(30) NOT NULL, fournisseur_id BINARY(16) NOT NULL, commande_fournisseur_id BINARY(16) DEFAULT NULL, total_amount NUMERIC(12, 2) NOT NULL, label VARCHAR(255) DEFAULT NULL, issued_at DATETIME NOT NULL, credit_closed_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_dette_fournisseur_reference (reference), INDEX idx_dette_fournisseur (fournisseur_id, credit_closed_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE paiements_fournisseur (reference VARCHAR(30) NOT NULL, dette_fournisseur_id BINARY(16) NOT NULL, amount NUMERIC(12, 2) NOT NULL, mode_de_paiement_id BINARY(16) NOT NULL, paid_at DATETIME NOT NULL, cancelled_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_paiement_fournisseur_reference (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE paiements_fournisseur');
        $this->addSql('DROP TABLE dettes_fournisseur');
        $this->addSql('DROP TABLE fournisseurs');
    }
}
