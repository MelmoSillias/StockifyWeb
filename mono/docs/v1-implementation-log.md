# Journal — Stockify mono

Le journal SaaS détaillé reste dans [`saas/docs/v1-implementation-log.md`](../../saas/docs/v1-implementation-log.md).

## État actuel (mono)

| Élément | Statut |
|---------|--------|
| API sans Tenancy / Platform | fait |
| Routes plates `/api/categories`, `/products`, … | fait |
| Fixtures owner/manager + catalogue démo | fait |
| Frontend sans sélection tenant | fait |
| Shop-configs (`dev:shop` / `build:shop`) | fait |

## Comptes fixtures

| Email | Username | Mot de passe |
|-------|----------|--------------|
| `owner@stockify.local` | `owner` | `Demo123!` |
| `manager@stockify.local` | `manager` | `Demo123!` |

## Prochaines étapes métier

Compléter le périmètre mono (ventes, caisse, crédits, …) avant réintégration dans le SaaS.
