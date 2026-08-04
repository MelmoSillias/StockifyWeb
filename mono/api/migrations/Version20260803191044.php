<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803191044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande_fournisseur_lines DROP FOREIGN KEY `FK_commande_fournisseur_line`');
        $this->addSql('DROP INDEX IDX_commande_fournisseur_line ON commande_fournisseur_lines');
        $this->addSql('ALTER TABLE commande_fournisseur_lines CHANGE commande_fournisseur_id commande_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE commande_fournisseur_lines ADD CONSTRAINT FK_A9681DF382EA2E54 FOREIGN KEY (commande_id) REFERENCES commandes_fournisseur (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A9681DF382EA2E54 ON commande_fournisseur_lines (commande_id)');
        $this->addSql('DROP INDEX uniq_commande_fournisseur_reference ON commandes_fournisseur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7B1B2A89AEA34913 ON commandes_fournisseur (reference)');
        $this->addSql('DROP INDEX idx_dette_fournisseur ON dettes_fournisseur');
        $this->addSql('DROP INDEX uniq_dette_fournisseur_reference ON dettes_fournisseur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97F26465AEA34913 ON dettes_fournisseur (reference)');
        $this->addSql('DROP INDEX uniq_paiement_fournisseur_reference ON paiements_fournisseur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FF51B1EFAEA34913 ON paiements_fournisseur (reference)');
        $this->addSql('ALTER TABLE print_settings CHANGE default_page_table default_page_table VARCHAR(255) NOT NULL, CHANGE default_page_facture default_page_facture VARCHAR(255) NOT NULL, CHANGE default_page_paiement default_page_paiement VARCHAR(255) NOT NULL, CHANGE default_page_vente default_page_vente VARCHAR(255) NOT NULL, CHANGE default_page_bon_livraison default_page_bon_livraison VARCHAR(255) NOT NULL, CHANGE default_page_transaction default_page_transaction VARCHAR(255) NOT NULL, CHANGE default_export_format default_export_format VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY `FK_8660A5A6A76ED395`');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY `FK_8660A5A6FED90CCA`');
        $this->addSql('DROP INDEX idx_8660a5a6a76ed395 ON user_permissions');
        $this->addSql('CREATE INDEX IDX_84F605FAA76ED395 ON user_permissions (user_id)');
        $this->addSql('DROP INDEX idx_8660a5a6fed90cca ON user_permissions');
        $this->addSql('CREATE INDEX IDX_84F605FAFED90CCA ON user_permissions (permission_id)');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT `FK_8660A5A6A76ED395` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT `FK_8660A5A6FED90CCA` FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_7b1b2a89aea34913 ON commandes_fournisseur');
        $this->addSql('CREATE UNIQUE INDEX uniq_commande_fournisseur_reference ON commandes_fournisseur (reference)');
        $this->addSql('ALTER TABLE commande_fournisseur_lines DROP FOREIGN KEY FK_A9681DF382EA2E54');
        $this->addSql('DROP INDEX IDX_A9681DF382EA2E54 ON commande_fournisseur_lines');
        $this->addSql('ALTER TABLE commande_fournisseur_lines CHANGE commande_id commande_fournisseur_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE commande_fournisseur_lines ADD CONSTRAINT `FK_commande_fournisseur_line` FOREIGN KEY (commande_fournisseur_id) REFERENCES commandes_fournisseur (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_commande_fournisseur_line ON commande_fournisseur_lines (commande_fournisseur_id)');
        $this->addSql('CREATE INDEX idx_dette_fournisseur ON dettes_fournisseur (fournisseur_id, credit_closed_at)');
        $this->addSql('DROP INDEX uniq_97f26465aea34913 ON dettes_fournisseur');
        $this->addSql('CREATE UNIQUE INDEX uniq_dette_fournisseur_reference ON dettes_fournisseur (reference)');
        $this->addSql('DROP INDEX uniq_ff51b1efaea34913 ON paiements_fournisseur');
        $this->addSql('CREATE UNIQUE INDEX uniq_paiement_fournisseur_reference ON paiements_fournisseur (reference)');
        $this->addSql('ALTER TABLE print_settings CHANGE default_page_table default_page_table VARCHAR(20) NOT NULL, CHANGE default_page_facture default_page_facture VARCHAR(20) NOT NULL, CHANGE default_page_paiement default_page_paiement VARCHAR(20) NOT NULL, CHANGE default_page_vente default_page_vente VARCHAR(20) NOT NULL, CHANGE default_page_bon_livraison default_page_bon_livraison VARCHAR(20) NOT NULL, CHANGE default_page_transaction default_page_transaction VARCHAR(20) NOT NULL, CHANGE default_export_format default_export_format VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY FK_84F605FAA76ED395');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY FK_84F605FAFED90CCA');
        $this->addSql('DROP INDEX idx_84f605faa76ed395 ON user_permissions');
        $this->addSql('CREATE INDEX IDX_8660A5A6A76ED395 ON user_permissions (user_id)');
        $this->addSql('DROP INDEX idx_84f605fafed90cca ON user_permissions');
        $this->addSql('CREATE INDEX IDX_8660A5A6FED90CCA ON user_permissions (permission_id)');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT FK_84F605FAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT FK_84F605FAFED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE');
    }
}
