<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Commerce: support free lines (nullable variant_id, line_type column)';
    }

    public function up(Schema $schema): void
    {
        foreach (['vente_lines', 'commande_lines', 'devis_lines', 'facture_lines', 'avoir_lines'] as $table) {
            $this->addSql(sprintf(
                'ALTER TABLE %s CHANGE variant_id variant_id BINARY(16) DEFAULT NULL, ADD line_type VARCHAR(20) NOT NULL DEFAULT \'product\'',
                $table,
            ));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['vente_lines', 'commande_lines', 'devis_lines', 'facture_lines', 'avoir_lines'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s DROP line_type', $table));
            $this->addSql(sprintf('ALTER TABLE %s CHANGE variant_id variant_id BINARY(16) NOT NULL', $table));
        }
    }
}
