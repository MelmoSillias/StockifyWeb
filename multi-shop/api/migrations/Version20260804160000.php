<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tenant_accounts, integration_request_logs and shops.tenant_account_id for Integration API';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tenant_accounts (external_account_id VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, entitlements_snapshot JSON NOT NULL, provisioned_at DATETIME DEFAULT NULL, last_synced_at DATETIME DEFAULT NULL, id BINARY(16) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_tenant_external_account_id (external_account_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE integration_request_logs (method VARCHAR(10) NOT NULL, path VARCHAR(255) NOT NULL, external_account_id VARCHAR(255) DEFAULT NULL, idempotency_key VARCHAR(255) DEFAULT NULL, request_summary JSON DEFAULT NULL, response_status INT DEFAULT NULL, response_body JSON DEFAULT NULL, duration_ms INT DEFAULT NULL, created_at DATETIME NOT NULL, id BINARY(16) NOT NULL, UNIQUE INDEX uniq_integration_idempotency_key (idempotency_key), INDEX idx_integration_log_external_account (external_account_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE shops ADD tenant_account_id BINARY(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shops DROP tenant_account_id');
        $this->addSql('DROP TABLE integration_request_logs');
        $this->addSql('DROP TABLE tenant_accounts');
    }
}
