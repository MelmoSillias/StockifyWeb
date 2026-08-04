<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804054007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_logs (shop_id BINARY(16) DEFAULT NULL, occurred_at DATETIME NOT NULL, user_id BINARY(16) DEFAULT NULL, user_email VARCHAR(180) DEFAULT NULL, action VARCHAR(100) NOT NULL, resource_type VARCHAR(100) DEFAULT NULL, resource_id BINARY(16) DEFAULT NULL, method VARCHAR(10) DEFAULT NULL, route VARCHAR(255) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(512) DEFAULT NULL, payload_summary JSON DEFAULT NULL, status VARCHAR(255) NOT NULL, duration_ms INT DEFAULT NULL, id BINARY(16) NOT NULL, INDEX idx_audit_occurred_user (occurred_at, user_id), INDEX idx_audit_action (action), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE avoir_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, avoir_id BINARY(16) NOT NULL, INDEX IDX_42F8F885C36D46DB (avoir_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE avoirs (numero VARCHAR(30) NOT NULL, facture_id BINARY(16) NOT NULL, vente_id BINARY(16) NOT NULL, total_amount NUMERIC(12, 2) NOT NULL, issued_at DATETIME NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_F4BE443BF55AE19E (numero), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bon_livraison_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, bon_de_livraison_id BINARY(16) NOT NULL, INDEX IDX_84E9C454BBB12796 (bon_de_livraison_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bons_livraison (reference VARCHAR(30) NOT NULL, commande_id BINARY(16) NOT NULL, status VARCHAR(255) NOT NULL, sent_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_3B32E0B8AEA34913 (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE clients (name VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, credit_limit NUMERIC(12, 2) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande_fournisseur_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_cost NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, commande_id BINARY(16) NOT NULL, INDEX IDX_A9681DF382EA2E54 (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, commande_id BINARY(16) NOT NULL, INDEX IDX_7669535582EA2E54 (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commandes (reference VARCHAR(30) NOT NULL, client_id BINARY(16) DEFAULT NULL, anonymous_info VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, total_amount NUMERIC(12, 2) NOT NULL, deposit_received NUMERIC(12, 2) NOT NULL, confirmed_at DATETIME DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, delivery_date DATE DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_35D4282CAEA34913 (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commandes_fournisseur (reference VARCHAR(30) NOT NULL, fournisseur_id BINARY(16) NOT NULL, status VARCHAR(255) NOT NULL, total_amount NUMERIC(12, 2) NOT NULL, deposit_paid NUMERIC(12, 2) NOT NULL, confirmed_at DATETIME DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, expected_at DATE DEFAULT NULL, received_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_7B1B2A89AEA34913 (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE comptes (name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, is_default TINYINT NOT NULL, is_active TINYINT NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dettes_fournisseur (reference VARCHAR(30) NOT NULL, fournisseur_id BINARY(16) NOT NULL, commande_fournisseur_id BINARY(16) DEFAULT NULL, total_amount NUMERIC(12, 2) NOT NULL, label VARCHAR(255) DEFAULT NULL, issued_at DATETIME NOT NULL, credit_closed_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_97F26465AEA34913 (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE facture_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, facture_id BINARY(16) NOT NULL, INDEX IDX_DD3504217F2DEE08 (facture_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE factures (numero VARCHAR(30) NOT NULL, vente_id BINARY(16) DEFAULT NULL, commande_id BINARY(16) DEFAULT NULL, source_reference VARCHAR(30) NOT NULL, client_id BINARY(16) DEFAULT NULL, anonymous_info VARCHAR(255) DEFAULT NULL, total_amount NUMERIC(12, 2) NOT NULL, issued_at DATETIME NOT NULL, is_creance TINYINT DEFAULT 0 NOT NULL, is_creance_finalized TINYINT DEFAULT 0 NOT NULL, credit_closed_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_647590BF55AE19E (numero), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE fournisseurs (name VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, deleted_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE modes_de_paiement (code VARCHAR(50) NOT NULL, label VARCHAR(255) NOT NULL, compte_id BINARY(16) NOT NULL, is_active TINYINT NOT NULL, generates_transaction TINYINT NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_mode_de_paiement_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE paiements (reference VARCHAR(30) NOT NULL, facture_id BINARY(16) DEFAULT NULL, commande_id BINARY(16) DEFAULT NULL, amount NUMERIC(12, 2) NOT NULL, mode_de_paiement_id BINARY(16) NOT NULL, paid_at DATETIME NOT NULL, cancelled_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_E1B02E12AEA34913 (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE paiements_fournisseur (reference VARCHAR(30) NOT NULL, dette_fournisseur_id BINARY(16) NOT NULL, amount NUMERIC(12, 2) NOT NULL, mode_de_paiement_id BINARY(16) NOT NULL, paid_at DATETIME NOT NULL, cancelled_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_FF51B1EFAEA34913 (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE permissions (code VARCHAR(100) NOT NULL, label VARCHAR(255) NOT NULL, module VARCHAR(50) NOT NULL, action VARCHAR(50) NOT NULL, is_critical TINYINT NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_permission_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE print_settings (shop_name VARCHAR(255) NOT NULL, address_lines JSON NOT NULL, phones JSON NOT NULL, email VARCHAR(255) DEFAULT NULL, logo_url VARCHAR(512) DEFAULT NULL, default_page_table VARCHAR(255) NOT NULL, default_page_facture VARCHAR(255) NOT NULL, default_page_paiement VARCHAR(255) NOT NULL, default_page_vente VARCHAR(255) NOT NULL, default_page_bon_livraison VARCHAR(255) NOT NULL, default_page_transaction VARCHAR(255) NOT NULL, default_export_format VARCHAR(255) NOT NULL, show_logo TINYINT NOT NULL, footer_text LONGTEXT DEFAULT NULL, margin_mm INT NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_categories (name VARCHAR(255) NOT NULL, sort_order INT NOT NULL, status VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, parent_id BINARY(16) DEFAULT NULL, INDEX IDX_A9941943727ACA70 (parent_id), UNIQUE INDEX uniq_category_name_parent (name, parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_variants (sku VARCHAR(100) NOT NULL, sale_mode VARCHAR(255) NOT NULL, default_price NUMERIC(12, 2) NOT NULL, alert_threshold NUMERIC(12, 3) DEFAULT NULL, status VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, product_id BINARY(16) NOT NULL, unit_of_measure_id BINARY(16) NOT NULL, INDEX IDX_782839764584665A (product_id), INDEX IDX_78283976DA4E2C90 (unit_of_measure_id), UNIQUE INDEX uniq_variant_sku (sku), UNIQUE INDEX uniq_variant_combo (product_id, unit_of_measure_id, sale_mode), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE products (name VARCHAR(255) NOT NULL, reference VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, category_id BINARY(16) DEFAULT NULL, INDEX IDX_B3BA5A5A12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE refresh_tokens (token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, revoked_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_9BACE7E1A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE roles (code VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, is_system TINYINT NOT NULL, is_active TINYINT NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_role_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role_permissions (role_id BINARY(16) NOT NULL, permission_id BINARY(16) NOT NULL, INDEX IDX_1FBA94E6D60322AC (role_id), INDEX IDX_1FBA94E6FED90CCA (permission_id), PRIMARY KEY (role_id, permission_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shops (name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, status VARCHAR(255) NOT NULL, currency VARCHAR(3) DEFAULT NULL, address LONGTEXT DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_shop_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_lot_allocations (quantity NUMERIC(12, 3) NOT NULL, unit_cost NUMERIC(12, 4) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, movement_id BINARY(16) NOT NULL, lot_id BINARY(16) NOT NULL, INDEX IDX_74348FE3229E70A7 (movement_id), INDEX IDX_74348FE3A8CBA5F7 (lot_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_lots (reference VARCHAR(100) DEFAULT NULL, quantity_initial NUMERIC(12, 3) NOT NULL, quantity_remaining NUMERIC(12, 3) NOT NULL, unit_cost NUMERIC(12, 4) NOT NULL, received_at DATETIME NOT NULL, supplier_ref VARCHAR(255) DEFAULT NULL, fournisseur_id BINARY(16) DEFAULT NULL, expiry_date DATE DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, variant_id BINARY(16) NOT NULL, INDEX IDX_887ADF0A3B69A9AF (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_movements (type VARCHAR(255) NOT NULL, direction VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_cost NUMERIC(12, 4) DEFAULT NULL, reason VARCHAR(255) DEFAULT NULL, source_ref VARCHAR(255) DEFAULT NULL, occurred_at DATETIME NOT NULL, created_at DATETIME NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, variant_id BINARY(16) NOT NULL, performed_by_id BINARY(16) DEFAULT NULL, INDEX IDX_A0BE93C93B69A9AF (variant_id), INDEX IDX_A0BE93C92E65C292 (performed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_policies (strategy VARCHAR(255) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, variant_id BINARY(16) NOT NULL, UNIQUE INDEX uniq_policy_variant (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transactions (compte_id BINARY(16) NOT NULL, type VARCHAR(255) NOT NULL, amount NUMERIC(12, 2) NOT NULL, label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, occurred_at DATETIME NOT NULL, source_type VARCHAR(255) NOT NULL, source_id BINARY(16) DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE units_of_measure (code VARCHAR(20) NOT NULL, label VARCHAR(100) NOT NULL, decimal_places INT NOT NULL, is_system TINYINT NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_unit_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_permissions (granted TINYINT NOT NULL, id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, permission_id BINARY(16) NOT NULL, INDEX IDX_84F605FAA76ED395 (user_id), INDEX IDX_84F605FAFED90CCA (permission_id), UNIQUE INDEX uniq_user_permission (user_id, permission_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_roles (id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, role_id BINARY(16) NOT NULL, INDEX IDX_54FCD59FA76ED395 (user_id), INDEX IDX_54FCD59FD60322AC (role_id), UNIQUE INDEX uniq_user_role (user_id, role_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (email VARCHAR(180) NOT NULL, username VARCHAR(50) NOT NULL, password_hash VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, status VARCHAR(255) NOT NULL, email_verified_at DATETIME DEFAULT NULL, last_login_at DATETIME DEFAULT NULL, roles JSON NOT NULL, shop_id BINARY(16) DEFAULT NULL, is_platform_owner TINYINT NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_user_email (email), UNIQUE INDEX uniq_user_shop_username (shop_id, username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vente_lines (variant_id BINARY(16) NOT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(12, 3) NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, vente_id BINARY(16) NOT NULL, INDEX IDX_5B1081DF7DC7170A (vente_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ventes (reference VARCHAR(30) NOT NULL, client_id BINARY(16) DEFAULT NULL, anonymous_info VARCHAR(255) DEFAULT NULL, total_amount NUMERIC(12, 2) NOT NULL, created_at DATETIME NOT NULL, cancelled_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, shop_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_64EC489AAEA34913 (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE avoir_lines ADD CONSTRAINT FK_42F8F885C36D46DB FOREIGN KEY (avoir_id) REFERENCES avoirs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bon_livraison_lines ADD CONSTRAINT FK_84E9C454BBB12796 FOREIGN KEY (bon_de_livraison_id) REFERENCES bons_livraison (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_fournisseur_lines ADD CONSTRAINT FK_A9681DF382EA2E54 FOREIGN KEY (commande_id) REFERENCES commandes_fournisseur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_lines ADD CONSTRAINT FK_7669535582EA2E54 FOREIGN KEY (commande_id) REFERENCES commandes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture_lines ADD CONSTRAINT FK_DD3504217F2DEE08 FOREIGN KEY (facture_id) REFERENCES factures (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_categories ADD CONSTRAINT FK_A9941943727ACA70 FOREIGN KEY (parent_id) REFERENCES product_categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product_variants ADD CONSTRAINT FK_782839764584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_variants ADD CONSTRAINT FK_78283976DA4E2C90 FOREIGN KEY (unit_of_measure_id) REFERENCES units_of_measure (id)');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5A12469DE2 FOREIGN KEY (category_id) REFERENCES product_categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE refresh_tokens ADD CONSTRAINT FK_9BACE7E1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_1FBA94E6D60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_1FBA94E6FED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_lot_allocations ADD CONSTRAINT FK_74348FE3229E70A7 FOREIGN KEY (movement_id) REFERENCES stock_movements (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_lot_allocations ADD CONSTRAINT FK_74348FE3A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES stock_lots (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_lots ADD CONSTRAINT FK_887ADF0A3B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_movements ADD CONSTRAINT FK_A0BE93C93B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock_movements ADD CONSTRAINT FK_A0BE93C92E65C292 FOREIGN KEY (performed_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stock_policies ADD CONSTRAINT FK_AF4DEB8C3B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT FK_84F605FAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT FK_84F605FAFED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FD60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vente_lines ADD CONSTRAINT FK_5B1081DF7DC7170A FOREIGN KEY (vente_id) REFERENCES ventes (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avoir_lines DROP FOREIGN KEY FK_42F8F885C36D46DB');
        $this->addSql('ALTER TABLE bon_livraison_lines DROP FOREIGN KEY FK_84E9C454BBB12796');
        $this->addSql('ALTER TABLE commande_fournisseur_lines DROP FOREIGN KEY FK_A9681DF382EA2E54');
        $this->addSql('ALTER TABLE commande_lines DROP FOREIGN KEY FK_7669535582EA2E54');
        $this->addSql('ALTER TABLE facture_lines DROP FOREIGN KEY FK_DD3504217F2DEE08');
        $this->addSql('ALTER TABLE product_categories DROP FOREIGN KEY FK_A9941943727ACA70');
        $this->addSql('ALTER TABLE product_variants DROP FOREIGN KEY FK_782839764584665A');
        $this->addSql('ALTER TABLE product_variants DROP FOREIGN KEY FK_78283976DA4E2C90');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5A12469DE2');
        $this->addSql('ALTER TABLE refresh_tokens DROP FOREIGN KEY FK_9BACE7E1A76ED395');
        $this->addSql('ALTER TABLE role_permissions DROP FOREIGN KEY FK_1FBA94E6D60322AC');
        $this->addSql('ALTER TABLE role_permissions DROP FOREIGN KEY FK_1FBA94E6FED90CCA');
        $this->addSql('ALTER TABLE stock_lot_allocations DROP FOREIGN KEY FK_74348FE3229E70A7');
        $this->addSql('ALTER TABLE stock_lot_allocations DROP FOREIGN KEY FK_74348FE3A8CBA5F7');
        $this->addSql('ALTER TABLE stock_lots DROP FOREIGN KEY FK_887ADF0A3B69A9AF');
        $this->addSql('ALTER TABLE stock_movements DROP FOREIGN KEY FK_A0BE93C93B69A9AF');
        $this->addSql('ALTER TABLE stock_movements DROP FOREIGN KEY FK_A0BE93C92E65C292');
        $this->addSql('ALTER TABLE stock_policies DROP FOREIGN KEY FK_AF4DEB8C3B69A9AF');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY FK_84F605FAA76ED395');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY FK_84F605FAFED90CCA');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59FA76ED395');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59FD60322AC');
        $this->addSql('ALTER TABLE vente_lines DROP FOREIGN KEY FK_5B1081DF7DC7170A');
        $this->addSql('DROP TABLE audit_logs');
        $this->addSql('DROP TABLE avoir_lines');
        $this->addSql('DROP TABLE avoirs');
        $this->addSql('DROP TABLE bon_livraison_lines');
        $this->addSql('DROP TABLE bons_livraison');
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE commande_fournisseur_lines');
        $this->addSql('DROP TABLE commande_lines');
        $this->addSql('DROP TABLE commandes');
        $this->addSql('DROP TABLE commandes_fournisseur');
        $this->addSql('DROP TABLE comptes');
        $this->addSql('DROP TABLE dettes_fournisseur');
        $this->addSql('DROP TABLE facture_lines');
        $this->addSql('DROP TABLE factures');
        $this->addSql('DROP TABLE fournisseurs');
        $this->addSql('DROP TABLE modes_de_paiement');
        $this->addSql('DROP TABLE paiements');
        $this->addSql('DROP TABLE paiements_fournisseur');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE print_settings');
        $this->addSql('DROP TABLE product_categories');
        $this->addSql('DROP TABLE product_variants');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE role_permissions');
        $this->addSql('DROP TABLE shops');
        $this->addSql('DROP TABLE stock_lot_allocations');
        $this->addSql('DROP TABLE stock_lots');
        $this->addSql('DROP TABLE stock_movements');
        $this->addSql('DROP TABLE stock_policies');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE units_of_measure');
        $this->addSql('DROP TABLE user_permissions');
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE vente_lines');
        $this->addSql('DROP TABLE ventes');
    }
}
