<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Impression module: print_settings table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE print_settings (
            shop_name VARCHAR(255) NOT NULL,
            address_lines JSON NOT NULL,
            phones JSON NOT NULL,
            email VARCHAR(255) DEFAULT NULL,
            logo_url VARCHAR(512) DEFAULT NULL,
            default_page_table VARCHAR(20) NOT NULL,
            default_page_facture VARCHAR(20) NOT NULL,
            default_page_paiement VARCHAR(20) NOT NULL,
            default_page_vente VARCHAR(20) NOT NULL,
            default_page_bon_livraison VARCHAR(20) NOT NULL,
            default_page_transaction VARCHAR(20) NOT NULL,
            default_export_format VARCHAR(20) NOT NULL,
            show_logo TINYINT NOT NULL,
            footer_text LONGTEXT DEFAULT NULL,
            margin_mm INT NOT NULL,
            id BINARY(16) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE print_settings');
    }
}
