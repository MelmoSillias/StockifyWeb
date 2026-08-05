# Integration API v1 — Stockify multi-shop

Implémentation côté Data Plane du contrat Control Plane ↔ Stockify.

## Base path

```
/integration/v1
```

## Authentification

Architecture complète CP ↔ DP (4 canaux, flux de provisioning, webhooks) : [sim-saas-admin/docs/auth-architecture.md](../../../sim-saas-admin/docs/auth-architecture.md).

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
| PATCH | `/accounts/{id}/entitlements` | JWT — sync entitlements (push CP → DP) |
| POST | `/accounts/{id}/suspend` | JWT — suspension |
| POST | `/accounts/{id}/activate` | JWT — réactivation |
| DELETE | `/accounts/{id}` | JWT — `?mode=guard` (défaut, refuse si shops) ou `?mode=purge` (hard delete, flag `TENANT_PURGE_ENABLED`) |
| GET | `/accounts/{id}/usage` | JWT — shops/users count |
| POST | `/accounts/{id}/shops` | JWT — créer boutique (Idempotency-Key + reprise même slug sous le tenant) |
| POST | `/accounts/{id}/users/invite` | JWT — invite / membership |

Voir aussi [identity-v1.md](identity-v1.md) pour l'authentification globale (Gap C).

## Résilience des entitlements (pull de secours)

Le Control Plane pousse le snapshot via `PATCH …/entitlements`. En complément, le Data Plane tire un snapshot à jour depuis le CP quand `TenantAccount.last_synced_at` est trop ancien (`ENTITLEMENT_STALE_AFTER_SECONDS`, défaut 86400).

- Déclenchement : `TenantFeatureGuard` avant quota/feature, et commande `integration:pull-stale-entitlements`
- Endpoint CP : `POST {CONTROL_PLANE_BASE_URL}/api/integration/v1/accounts/{accountId}/entitlements/pull` (HMAC `X-Integration-Signature`)
- Panne CP : le dernier snapshot local est conservé (fail-open sur features déjà accordées, fail-closed sur features absentes du snapshot)

## Suppression

- **guard** (défaut) : 204 si aucune boutique, 409 sinon.
- **purge** : nécessite `TENANT_PURGE_ENABLED=1`. Supprime données métier, users, shops, puis `TenantAccount`. Répond **202** avec `deletion_receipt`.

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
