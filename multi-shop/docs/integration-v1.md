# Integration API v1 — Stockify multi-shop

Implémentation côté Data Plane du contrat Control Plane ↔ Stockify.

## Base path

```
/integration/v1
```

## Authentification

- Firewall dédié (`integration` dans `security.yaml`)
- Bearer JWT signé RS256 par le Control Plane
- Variable d'environnement : `INTEGRATION_JWT_PUBLIC_KEY`
- Flag : `INTEGRATION_ENABLED=true`

## Modèle TenantAccount

```
Control Plane Account (UUID)
        │ externalAccountId
        ▼
TenantAccount (Stockify)
        │ tenantAccountId
        ▼
Shop(s)
```

## Endpoints

| Méthode | Path | Description |
|---------|------|-------------|
| GET | `/health` | Public — status, version |
| GET | `/capabilities` | JWT — capacités supportées |
| POST | `/accounts` | JWT — provision idempotent |
| GET | `/accounts/{id}` | JWT — état tenant |
| PATCH | `/accounts/{id}/entitlements` | JWT — sync entitlements |
| POST | `/accounts/{id}/suspend` | JWT — suspension |
| POST | `/accounts/{id}/activate` | JWT — réactivation |
| DELETE | `/accounts/{id}` | JWT — suppression (si pas de shops) |
| GET | `/accounts/{id}/usage` | JWT — shops/users count |
| POST | `/accounts/{id}/shops` | JWT — créer boutique |

## Suspension

Quand `TenantAccount.status = suspended`, `ShopContextResolver` bloque l'accès métier (403).

## Migration legacy

```bash
php bin/console integration:migrate-legacy-shops --export=var/legacy-tenants.json
php bin/console integration:migrate-legacy-shops --dry-run
```

## Tests

```bash
php bin/phpunit tests/Integration/IntegrationApiTest.php
```

## Spec OpenAPI

Voir `sim-saas-admin/docs/integration-contract-v1.openapi.yaml`.
