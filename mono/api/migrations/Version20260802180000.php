<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'AccessAudit module: roles, permissions, user_roles, user_permissions, audit_logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE permissions (code VARCHAR(100) NOT NULL, label VARCHAR(255) NOT NULL, module VARCHAR(50) NOT NULL, action VARCHAR(50) NOT NULL, is_critical TINYINT NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_permission_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE roles (code VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, is_system TINYINT NOT NULL, is_active TINYINT NOT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_role_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role_permissions (role_id BINARY(16) NOT NULL, permission_id BINARY(16) NOT NULL, INDEX IDX_1FBA94E6D60322AC (role_id), INDEX IDX_1FBA94E6FED90CCA (permission_id), PRIMARY KEY (role_id, permission_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_roles (id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, role_id BINARY(16) NOT NULL, INDEX IDX_54FCD59FA76ED395 (user_id), INDEX IDX_54FCD59FD60322AC (role_id), UNIQUE INDEX uniq_user_role (user_id, role_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_permissions (granted TINYINT NOT NULL, id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, permission_id BINARY(16) NOT NULL, INDEX IDX_8660A5A6A76ED395 (user_id), INDEX IDX_8660A5A6FED90CCA (permission_id), UNIQUE INDEX uniq_user_permission (user_id, permission_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE audit_logs (occurred_at DATETIME NOT NULL, user_id BINARY(16) DEFAULT NULL, user_email VARCHAR(180) DEFAULT NULL, action VARCHAR(100) NOT NULL, resource_type VARCHAR(100) DEFAULT NULL, resource_id BINARY(16) DEFAULT NULL, method VARCHAR(10) DEFAULT NULL, route VARCHAR(255) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(512) DEFAULT NULL, payload_summary JSON DEFAULT NULL, status VARCHAR(255) NOT NULL, duration_ms INT DEFAULT NULL, id BINARY(16) NOT NULL, INDEX idx_audit_occurred_user (occurred_at, user_id), INDEX idx_audit_action (action), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_1FBA94E6D60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_1FBA94E6FED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FD60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT FK_8660A5A6A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT FK_8660A5A6FED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE role_permissions DROP FOREIGN KEY FK_1FBA94E6D60322AC');
        $this->addSql('ALTER TABLE role_permissions DROP FOREIGN KEY FK_1FBA94E6FED90CCA');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59FA76ED395');
        $this->addSql('ALTER TABLE user_roles DROP FOREIGN KEY FK_54FCD59FD60322AC');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY FK_8660A5A6A76ED395');
        $this->addSql('ALTER TABLE user_permissions DROP FOREIGN KEY FK_8660A5A6FED90CCA');
        $this->addSql('DROP TABLE audit_logs');
        $this->addSql('DROP TABLE user_permissions');
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('DROP TABLE role_permissions');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE permissions');
    }
}
