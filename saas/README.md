# Stockify SaaS — en pause

Cette arborescence contient la version **multi-compte / multi-boutique** (tenancy, plateforme super-admin).

Le développement actif se fait dans [`../mono/`](../mono/).  
Quand le métier mono-magasin sera complet, il sera réintégré ici.

## Contenu

| Dossier | Rôle |
|---------|------|
| `api/` | Backend Symfony multi-tenant |
| `user-frontend/` | App métier (sélection compte/boutique) |
| `super-admin-frontend/` | Console plateforme |
| `docs/` | Conception SaaS V1 |
| `scripts/` | Bootstrap / start API |

Ne pas déployer tant que le mono n’a pas stabilisé le domaine métier.
