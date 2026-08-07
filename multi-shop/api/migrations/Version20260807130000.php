<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Consolidate identity auth: nullable password_hash, drop legacy user columns, global username uniqueness.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users MODIFY password_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE users SET password_hash = NULL WHERE identity_id IS NOT NULL');
        $this->addSql(<<<'SQL'
            INSERT INTO user_shop_memberships (id, user_id, shop_id, is_primary, created_at)
            SELECT UNHEX(REPLACE(UUID(), '-', '')), u.id, u.shop_id, 1, NOW()
            FROM users u
            WHERE u.shop_id IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM user_shop_memberships m
                WHERE m.user_id = u.id AND m.shop_id = u.shop_id
            )
        SQL);
        $this->addSql('DROP INDEX uniq_user_shop_username ON users');
        $this->addSql('ALTER TABLE users DROP shop_id, DROP roles, DROP legacy_email');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_username ON users (username)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_user_username ON users');
        $this->addSql('ALTER TABLE users ADD shop_id BINARY(16) DEFAULT NULL, ADD roles JSON NOT NULL, ADD legacy_email VARCHAR(180) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE users u
            INNER JOIN user_shop_memberships m ON m.user_id = u.id AND m.is_primary = 1
            SET u.shop_id = m.shop_id
        SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_user_shop_username ON users (shop_id, username)');
        $this->addSql('ALTER TABLE users MODIFY password_hash VARCHAR(255) NOT NULL');
    }
}
