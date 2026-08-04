<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260801092422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_livraison_lines DROP FOREIGN KEY `FK_bon_livraison_line_bl`');
        $this->addSql('DROP INDEX IDX_bon_livraison_line_bl ON bon_livraison_lines');
        $this->addSql('ALTER TABLE bon_livraison_lines CHANGE bon_livraison_id bon_de_livraison_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE bon_livraison_lines ADD CONSTRAINT FK_84E9C454BBB12796 FOREIGN KEY (bon_de_livraison_id) REFERENCES bons_livraison (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_84E9C454BBB12796 ON bon_livraison_lines (bon_de_livraison_id)');
        $this->addSql('DROP INDEX idx_bon_livraison_commande ON bons_livraison');
        $this->addSql('DROP INDEX uniq_bon_livraison_reference ON bons_livraison');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3B32E0B8AEA34913 ON bons_livraison (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_bon_livraison_commande ON bons_livraison (commande_id)');
        $this->addSql('DROP INDEX uniq_3b32e0b8aea34913 ON bons_livraison');
        $this->addSql('CREATE UNIQUE INDEX uniq_bon_livraison_reference ON bons_livraison (reference)');
        $this->addSql('ALTER TABLE bon_livraison_lines DROP FOREIGN KEY FK_84E9C454BBB12796');
        $this->addSql('DROP INDEX IDX_84E9C454BBB12796 ON bon_livraison_lines');
        $this->addSql('ALTER TABLE bon_livraison_lines CHANGE bon_de_livraison_id bon_livraison_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE bon_livraison_lines ADD CONSTRAINT `FK_bon_livraison_line_bl` FOREIGN KEY (bon_livraison_id) REFERENCES bons_livraison (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_bon_livraison_line_bl ON bon_livraison_lines (bon_livraison_id)');
    }
}
