<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250513135156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE creance_fournisseur (id INT AUTO_INCREMENT NOT NULL, fournisseur_nom VARCHAR(255) NOT NULL, devise VARCHAR(3) NOT NULL, taux_change DOUBLE PRECISION NOT NULL, montant_total DOUBLE PRECISION NOT NULL, montant_restant DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE credit_client (id INT AUTO_INCREMENT NOT NULL, vente_id INT NOT NULL, client_nom VARCHAR(255) DEFAULT NULL, montant_total DOUBLE PRECISION NOT NULL, montant_restant DOUBLE PRECISION NOT NULL, statut VARCHAR(255) NOT NULL, INDEX IDX_F672E5347DC7170A (vente_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE detail_vente (id INT AUTO_INCREMENT NOT NULL, vente_id INT NOT NULL, produit_id INT NOT NULL, quantite INT NOT NULL, prix_unitaire_vente DOUBLE PRECISION NOT NULL, INDEX IDX_F57AE1157DC7170A (vente_id), INDEX IDX_F57AE115F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE lot_produit (id INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, quantite INT NOT NULL, prix_unitaire_achat DOUBLE PRECISION NOT NULL, date_achat DATETIME NOT NULL, fournisseur VARCHAR(255) DEFAULT NULL, devise VARCHAR(3) DEFAULT NULL, INDEX IDX_8425AA04F347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE mouvement_stock (id INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, type VARCHAR(255) NOT NULL, quantite INT NOT NULL, date DATETIME NOT NULL, source VARCHAR(255) NOT NULL, commentaire VARCHAR(255) DEFAULT NULL, INDEX IDX_61E2C8EBF347EFB (produit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE paiement_creance_fournisseur (id INT AUTO_INCREMENT NOT NULL, creance_id INT NOT NULL, user_id INT DEFAULT NULL, montant_paye_devise DOUBLE PRECISION NOT NULL, taux_applique DOUBLE PRECISION NOT NULL, montant_en_caisse DOUBLE PRECISION NOT NULL, date DATETIME NOT NULL, INDEX IDX_332AC600CB51AD7C (creance_id), INDEX IDX_332AC600A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE paiement_credit_client (id INT AUTO_INCREMENT NOT NULL, credit_id INT NOT NULL, user_id INT DEFAULT NULL, montant DOUBLE PRECISION NOT NULL, date DATETIME NOT NULL, INDEX IDX_B3130F7ACE062FF9 (credit_id), INDEX IDX_B3130F7AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, reference VARCHAR(255) DEFAULT NULL, categorie VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, stock_actuel INT NOT NULL, pme DOUBLE PRECISION NOT NULL, seuil_alerte INT DEFAULT NULL, actif TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE transaction_caisse (id INT AUTO_INCREMENT NOT NULL, vente_id INT DEFAULT NULL, paiement_credit_id INT DEFAULT NULL, paiement_fournisseur_id INT DEFAULT NULL, date DATETIME NOT NULL, type VARCHAR(255) NOT NULL, montant DOUBLE PRECISION NOT NULL, description VARCHAR(255) NOT NULL, motif VARCHAR(255) NOT NULL, INDEX IDX_C4DE21887DC7170A (vente_id), INDEX IDX_C4DE21888C051B3 (paiement_credit_id), INDEX IDX_C4DE2188F62D7802 (paiement_fournisseur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom_utilisateur VARCHAR(180) NOT NULL, roles JSON NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, actif TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649D37CC8AC (nom_utilisateur), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE vente (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, date DATETIME NOT NULL, nom_client VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, total DOUBLE PRECISION NOT NULL, montant_paye DOUBLE PRECISION NOT NULL, reste DOUBLE PRECISION NOT NULL, INDEX IDX_888A2A4CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE credit_client ADD CONSTRAINT FK_F672E5347DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE detail_vente ADD CONSTRAINT FK_F57AE1157DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE detail_vente ADD CONSTRAINT FK_F57AE115F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_produit ADD CONSTRAINT FK_8425AA04F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EBF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_creance_fournisseur ADD CONSTRAINT FK_332AC600CB51AD7C FOREIGN KEY (creance_id) REFERENCES creance_fournisseur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_creance_fournisseur ADD CONSTRAINT FK_332AC600A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_credit_client ADD CONSTRAINT FK_B3130F7ACE062FF9 FOREIGN KEY (credit_id) REFERENCES credit_client (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_credit_client ADD CONSTRAINT FK_B3130F7AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction_caisse ADD CONSTRAINT FK_C4DE21887DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction_caisse ADD CONSTRAINT FK_C4DE21888C051B3 FOREIGN KEY (paiement_credit_id) REFERENCES paiement_credit_client (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction_caisse ADD CONSTRAINT FK_C4DE2188F62D7802 FOREIGN KEY (paiement_fournisseur_id) REFERENCES paiement_creance_fournisseur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE credit_client DROP FOREIGN KEY FK_F672E5347DC7170A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE detail_vente DROP FOREIGN KEY FK_F57AE1157DC7170A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE detail_vente DROP FOREIGN KEY FK_F57AE115F347EFB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE lot_produit DROP FOREIGN KEY FK_8425AA04F347EFB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EBF347EFB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_creance_fournisseur DROP FOREIGN KEY FK_332AC600CB51AD7C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_creance_fournisseur DROP FOREIGN KEY FK_332AC600A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_credit_client DROP FOREIGN KEY FK_B3130F7ACE062FF9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE paiement_credit_client DROP FOREIGN KEY FK_B3130F7AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction_caisse DROP FOREIGN KEY FK_C4DE21887DC7170A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction_caisse DROP FOREIGN KEY FK_C4DE21888C051B3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction_caisse DROP FOREIGN KEY FK_C4DE2188F62D7802
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE creance_fournisseur
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE credit_client
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE detail_vente
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE lot_produit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE mouvement_stock
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE paiement_creance_fournisseur
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE paiement_credit_client
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE produit
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE transaction_caisse
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE vente
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
