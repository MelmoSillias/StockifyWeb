<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * V3 Finance: comptes, modes de paiement, transactions + paiement mode refactor.
 */
final class Version20260801180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'V3 finance: comptes, modes_de_paiement, transactions, paiements.mode_de_paiement_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE comptes (name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, is_default TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE modes_de_paiement (code VARCHAR(50) NOT NULL, label VARCHAR(255) NOT NULL, compte_id BINARY(16) NOT NULL, is_active TINYINT(1) NOT NULL, generates_transaction TINYINT(1) NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_mode_de_paiement_code (code), INDEX IDX_mode_compte (compte_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transactions (compte_id BINARY(16) NOT NULL, type VARCHAR(255) NOT NULL, amount NUMERIC(12, 2) NOT NULL, label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, occurred_at DATETIME NOT NULL, source_type VARCHAR(255) NOT NULL, source_id BINARY(16) DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, INDEX IDX_transaction_compte (compte_id), INDEX IDX_transaction_source (source_type, source_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE modes_de_paiement ADD CONSTRAINT FK_mode_compte FOREIGN KEY (compte_id) REFERENCES comptes (id)');

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $caisseId = '018f00000000700080000000000001';
        $banqueId = '018f00000000700080000000000002';

        $this->addSql(sprintf(
            "INSERT INTO comptes (id, name, type, is_default, is_active, created_at, updated_at) VALUES (UNHEX('%s'), 'Caisse', 'caisse', 1, 1, '%s', '%s')",
            $caisseId,
            $now,
            $now,
        ));
        $this->addSql(sprintf(
            "INSERT INTO comptes (id, name, type, is_default, is_active, created_at, updated_at) VALUES (UNHEX('%s'), 'Compte bancaire', 'banque', 0, 1, '%s', '%s')",
            $banqueId,
            $now,
            $now,
        ));

        $modes = [
            ['018f00000000700080000000000011', 'cash', 'Espèces', $caisseId, 1],
            ['018f00000000700080000000000012', 'mobile_money', 'Mobile Money', $caisseId, 1],
            ['018f00000000700080000000000013', 'card', 'Carte', $banqueId, 1],
            ['018f00000000700080000000000014', 'transfer', 'Virement', $banqueId, 1],
            ['018f00000000700080000000000015', 'credit', 'Crédit', $caisseId, 0],
        ];

        foreach ($modes as [$modeId, $code, $label, $compteId, $generates]) {
            $this->addSql(sprintf(
                "INSERT INTO modes_de_paiement (id, code, label, compte_id, is_active, generates_transaction, created_at, updated_at) VALUES (UNHEX('%s'), '%s', '%s', UNHEX('%s'), 1, %d, '%s', '%s')",
                $modeId,
                $code,
                addslashes($label),
                $compteId,
                $generates,
                $now,
                $now,
            ));
        }

        $this->addSql('ALTER TABLE paiements ADD mode_de_paiement_id BINARY(16) DEFAULT NULL');
        $this->addSql('UPDATE paiements p INNER JOIN modes_de_paiement m ON m.code = p.method SET p.mode_de_paiement_id = m.id');
        $this->addSql("UPDATE paiements SET mode_de_paiement_id = UNHEX('018f00000000700080000000000011') WHERE mode_de_paiement_id IS NULL");
        $this->addSql('ALTER TABLE paiements DROP method');
        $this->addSql('ALTER TABLE paiements CHANGE mode_de_paiement_id mode_de_paiement_id BINARY(16) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE paiements ADD method VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE paiements p INNER JOIN modes_de_paiement m ON m.id = p.mode_de_paiement_id SET p.method = m.code');
        $this->addSql("UPDATE paiements SET method = 'cash' WHERE method IS NULL");
        $this->addSql('ALTER TABLE paiements DROP mode_de_paiement_id');
        $this->addSql('ALTER TABLE modes_de_paiement DROP FOREIGN KEY FK_mode_compte');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE modes_de_paiement');
        $this->addSql('DROP TABLE comptes');
    }
}
