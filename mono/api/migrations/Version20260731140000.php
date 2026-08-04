<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vente cancellation and credit notes (avoirs)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ventes ADD cancelled_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE TABLE avoirs (numero VARCHAR(30) NOT NULL, facture_id BINARY(16) NOT NULL, vente_id BINARY(16) NOT NULL, total_amount NUMERIC(12, 2) NOT NULL, issued_at DATETIME NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_avoir_numero (numero), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE avoir_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, avoir_id BINARY(16) NOT NULL, INDEX IDX_avoir_line_avoir (avoir_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE avoir_lines ADD CONSTRAINT FK_avoir_line_avoir FOREIGN KEY (avoir_id) REFERENCES avoirs (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avoir_lines DROP FOREIGN KEY FK_avoir_line_avoir');
        $this->addSql('DROP TABLE avoir_lines');
        $this->addSql('DROP TABLE avoirs');
        $this->addSql('ALTER TABLE ventes DROP cancelled_at');
    }
}
