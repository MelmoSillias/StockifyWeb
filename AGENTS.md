# Instructions pour les agents — StockifyWeb

## Règle de synchronisation core boutique

Toute modification touchant le **core multi-boutique** doit être répliquée entre :

- `multi-shop/api/src/Shop/` (bounded context Shop)
- `multi-shop/api/src/SharedKernel/` (ShopContext, ShopScopedTrait, ShopScopeFilter)
- `mono/api/src/Shop/` (quand le mono intégrera le core)

**Fichiers concernés :**

- Entité `Shop`, enums et value objects (`Email`, `Username`, `GeneratedPassword`)
- `ShopContext`, `ShopContextHolder`, `ShopContextResolver`, `ShopContextSubscriber`
- `ShopScopedTrait`, `ShopScopeFilter`
- Générateurs `ShopEmailGenerator`, `ShopPasswordGenerator`
- Paramètre `AUTH_IDENTIFIER` et infrastructure d'authentification associée
- Permissions platform : `platform.shops.*`, `platform.shop_users.*`

**Ne pas modifier `saas/`** — référence historique uniquement.

**Le mono reste single-shop** jusqu'à intégration explicite. Ne pas y ajouter de scoping shop sans demande explicite.

## Stacks du dépôt

| Dossier | Rôle |
|---------|------|
| `mono/` | Instance mono-magasin active (production actuelle) |
| `multi-shop/` | Expérimental — multi-boutique OWNER + Shop (chemin vers SaaS) |
| `saas/` | En pause — multi-compte / super-admin (référence) |

## Ports dev multi-shop

- API : `http://localhost:8001`
- Frontend : `http://localhost:5176`
- Base MySQL : `stockify_multishop`
