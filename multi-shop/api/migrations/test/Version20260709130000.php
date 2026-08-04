<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add username column and unique constraint on users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD username VARCHAR(50) NOT NULL DEFAULT ''");
        $this->addSql("UPDATE users SET username = LOWER(CONCAT(SUBSTRING_INDEX(email, '@', 1), '-', RIGHT(REPLACE(id, '-', ''), 6))) WHERE username = ''");
        $this->addSql('ALTER TABLE users ADD CONSTRAINT uniq_user_username UNIQUE (username)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP INDEX uniq_user_username');
        $this->addSql('ALTER TABLE users DROP username');
    }
}
