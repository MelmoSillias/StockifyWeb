<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bons de livraison (bons_livraison, bon_livraison_lines)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bons_livraison (reference VARCHAR(30) NOT NULL, commande_id BINARY(16) NOT NULL, status VARCHAR(255) NOT NULL, sent_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_bon_livraison_reference (reference), INDEX idx_bon_livraison_commande (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bon_livraison_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, id BINARY(16) NOT NULL, bon_livraison_id BINARY(16) NOT NULL, INDEX IDX_bon_livraison_line_bl (bon_livraison_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bon_livraison_lines ADD CONSTRAINT FK_bon_livraison_line_bl FOREIGN KEY (bon_livraison_id) REFERENCES bons_livraison (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bon_livraison_lines DROP FOREIGN KEY FK_bon_livraison_line_bl');
        $this->addSql('DROP TABLE bon_livraison_lines');
        $this->addSql('DROP TABLE bons_livraison');
    }
}
