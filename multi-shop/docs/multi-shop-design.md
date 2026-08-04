# Conception multi-shop — StockifyWeb

## Objectif

Tenancy simplifiée : un **OWNER** platform gère plusieurs **boutiques (Shop)**, chaque utilisateur appartient à une seule boutique. Isolation stricte des données via `shop_id`.

## Modèle

```
OWNER (shopId = null, ROLE_PLATFORM_OWNER)
  └── Shop A
        ├── User alice (username unique dans A)
        └── User bob
  └── Shop B
        └── User alice (même username autorisé, shop différent)
```

- Email OWNER : réel (`owner@example.com`)
- Email utilisateur boutique : `{username}@{shop_slug}.local`
- Mot de passe généré : `{Mot}{4 chiffres}!` (ex. `Boutique4821!`)

## Contexte shop actif

Le frontend envoie `X-Shop-Id` sur chaque requête API métier. Le backend :

1. Valide l'accès (OWNER ou `user.shopId == X-Shop-Id`)
2. Active le filtre Doctrine `ShopScopeFilter`
3. Assigne `shop_id` à toute création d'entité

## Phases

1. **Bootstrap** — copie mono, configs dédiées
2. **Domain Shop** — entité Shop, User.shopId, ROLE_PLATFORM_OWNER
3. **Auth + users** — AUTH_IDENTIFIER, CreateShopUser, générateurs
4. **Isolation** — ShopScopedTrait sur entités métier, SQLFilter
5. **API** — CRUD shops, users par shop, /me enrichi
6. **Frontend** — shopStore, ShopSelector, Admin/Boutiques

## Entités scopées

Catalog, Inventory, Client, Commerce, Facturation, Paiement, Livraison, Finance, Fournisseur, Impression, AccessAudit (AuditLog, UserRole).

**Globales :** Shop, User, Role, Permission, UnitOfMeasure.

## Évolution vers SaaS

Ce modèle est une étape intermédiaire avant `saas/` (Account + super-admin). Ne pas réintroduire la couche Account ici.
