<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514150643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_credit_client ADD paiement_credit_client_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_credit_client ADD CONSTRAINT FK_B3130F7A3C017514 FOREIGN KEY (paiement_credit_client_id) REFERENCES paiement_credit_client (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_B3130F7A3C017514 ON paiement_credit_client (paiement_credit_client_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction_caisse DROP INDEX IDX_C4DE2188F62D7802, ADD UNIQUE INDEX UNIQ_C4DE2188F62D7802 (paiement_fournisseur_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction_caisse DROP INDEX IDX_C4DE21888C051B3, ADD UNIQUE INDEX UNIQ_C4DE21888C051B3 (paiement_credit_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_credit_client DROP FOREIGN KEY FK_B3130F7A3C017514
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_B3130F7A3C017514 ON paiement_credit_client
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_credit_client DROP paiement_credit_client_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction_caisse DROP INDEX UNIQ_C4DE21888C051B3, ADD INDEX IDX_C4DE21888C051B3 (paiement_credit_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction_caisse DROP INDEX UNIQ_C4DE2188F62D7802, ADD INDEX IDX_C4DE2188F62D7802 (paiement_fournisseur_id)
        SQL);
    }
}
