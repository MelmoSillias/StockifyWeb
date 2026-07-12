<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema — Stockify mono (single shop).
 */
final class Version20260709095841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial mono schema: users, catalog, inventory (no multi-tenant)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (email VARCHAR(180) NOT NULL, username VARCHAR(50) NOT NULL, password_hash VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, status VARCHAR(255) NOT NULL, email_verified_at DATETIME DEFAULT NULL, last_login_at DATETIME DEFAULT NULL, roles JSON NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_user_email (email), UNIQUE INDEX uniq_user_username (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE refresh_tokens (token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, revoked_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_9BACE7E1A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE units_of_measure (code VARCHAR(20) NOT NULL, label VARCHAR(100) NOT NULL, decimal_places INT NOT NULL, is_system TINYINT NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_unit_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_categories (name VARCHAR(255) NOT NULL, sort_order INT NOT NULL, status VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, parent_id BINARY(16) DEFAULT NULL, INDEX IDX_A9941943727ACA70 (parent_id), UNIQUE INDEX uniq_category_name_parent (name, parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE products (name VARCHAR(255) NOT NULL, reference VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, category_id BINARY(16) DEFAULT NULL, INDEX IDX_B3BA5A5A12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_variants (sku VARCHAR(100) NOT NULL, sale_mode VARCHAR(255) NOT NULL, default_price NUMERIC(12, 2) NOT NULL, alert_threshold NUMERIC(12, 3) DEFAULT NULL, status VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, product_id BINARY(16) NOT NULL, unit_of_measure_id BINARY(16) NOT NULL, INDEX IDX_782839764584665A (product_id), INDEX IDX_78283976DA4E2C90 (unit_of_measure_id), UNIQUE INDEX uniq_variant_sku (sku), UNIQUE INDEX uniq_variant_combo (product_id, unit_of_measure_id, sale_mode), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_lots (reference VARCHAR(100) DEFAULT NULL, quantity_initial NUMERIC(12, 3) NOT NULL, quantity_remaining NUMERIC(12, 3) NOT NULL, unit_cost NUMERIC(12, 4) NOT NULL, received_at DATETIME NOT NULL, supplier_ref VARCHAR(255) DEFAULT NULL, expiry_date DATE DEFAULT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, variant_id BINARY(16) NOT NULL, INDEX IDX_887ADF0A3B69A9AF (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_movements (type VARCHAR(255) NOT NULL, direction VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_cost NUMERIC(12, 4) DEFAULT NULL, reason VARCHAR(255) DEFAULT NULL, source_ref VARCHAR(255) DEFAULT NULL, occurred_at DATETIME NOT NULL, created_at DATETIME NOT NULL, id BINARY(16) NOT NULL, variant_id BINARY(16) NOT NULL, performed_by_id BINARY(16) DEFAULT NULL, INDEX IDX_A0BE93C93B69A9AF (variant_id), INDEX IDX_A0BE93C92E65C292 (performed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_lot_allocations (quantity NUMERIC(12, 3) NOT NULL, unit_cost NUMERIC(12, 4) NOT NULL, id BINARY(16) NOT NULL, movement_id BINARY(16) NOT NULL, lot_id BINARY(16) NOT NULL, INDEX IDX_74348FE3229E70A7 (movement_id), INDEX IDX_74348FE3A8CBA5F7 (lot_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_policies (strategy VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, variant_id BINARY(16) NOT NULL, UNIQUE INDEX uniq_policy_variant (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE refresh_tokens ADD CONSTRAINT FK_9BACE7E1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_categories ADD CONSTRAINT FK_A9941943727ACA70 FOREIGN KEY (parent_id) REFERENCES product_categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5A12469DE2 FOREIGN KEY (category_id) REFERENCES product_categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product_variants ADD CONSTRAINT FK_782839764584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_variants ADD CONSTRAINT FK_78283976DA4E2C90 FOREIGN KEY (unit_of_measure_id) REFERENCES units_of_measure (id)');
        $this->addSql('ALTER TABLE stock_lots ADD CONSTRAINT FK_887ADF0A3B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_movements ADD CONSTRAINT FK_A0BE93C93B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_movements ADD CONSTRAINT FK_A0BE93C92E65C292 FOREIGN KEY (performed_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stock_lot_allocations ADD CONSTRAINT FK_74348FE3229E70A7 FOREIGN KEY (movement_id) REFERENCES stock_movements (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_lot_allocations ADD CONSTRAINT FK_74348FE3A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES stock_lots (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_policies ADD CONSTRAINT FK_AF4DEB8C3B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_tokens DROP FOREIGN KEY FK_9BACE7E1A76ED395');
        $this->addSql('ALTER TABLE product_categories DROP FOREIGN KEY FK_A9941943727ACA70');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5A12469DE2');
        $this->addSql('ALTER TABLE product_variants DROP FOREIGN KEY FK_782839764584665A');
        $this->addSql('ALTER TABLE product_variants DROP FOREIGN KEY FK_78283976DA4E2C90');
        $this->addSql('ALTER TABLE stock_lots DROP FOREIGN KEY FK_887ADF0A3B69A9AF');
        $this->addSql('ALTER TABLE stock_movements DROP FOREIGN KEY FK_A0BE93C93B69A9AF');
        $this->addSql('ALTER TABLE stock_movements DROP FOREIGN KEY FK_A0BE93C92E65C292');
        $this->addSql('ALTER TABLE stock_lot_allocations DROP FOREIGN KEY FK_74348FE3229E70A7');
        $this->addSql('ALTER TABLE stock_lot_allocations DROP FOREIGN KEY FK_74348FE3A8CBA5F7');
        $this->addSql('ALTER TABLE stock_policies DROP FOREIGN KEY FK_AF4DEB8C3B69A9AF');
        $this->addSql('DROP TABLE stock_lot_allocations');
        $this->addSql('DROP TABLE stock_policies');
        $this->addSql('DROP TABLE stock_movements');
        $this->addSql('DROP TABLE stock_lots');
        $this->addSql('DROP TABLE product_variants');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE product_categories');
        $this->addSql('DROP TABLE units_of_measure');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE users');
    }
}
