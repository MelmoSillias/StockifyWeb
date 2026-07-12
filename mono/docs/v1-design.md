# Conception — Stockify mono (un magasin)

Socle métier pour **une instance = un magasin**. Le multi-tenant SaaS est en pause sous `saas/`.

Référence legacy MVC : [data-model-mvc.md](data-model-mvc.md).

---

## Vision

1. Authentifier des utilisateurs (JWT).
2. Gérer le catalogue (catégories, produits, variantes).
3. Gérer le stock (lots, mouvements, politiques FIFO/LIFO/FEFO, alertes).

| Sujet | Décision |
|-------|----------|
| Architecture | Modular monolith Symfony |
| Isolation | Une base / une instance par magasin |
| Stock | Calculé depuis les lots (`SUM(quantity_remaining)`) |
| Modules | `Catalog` + `Inventory` séparés |
| PK | UUID |
| Auth | JWT — pas de headers tenant |

---

## Modules

| Module | Responsabilité |
|--------|----------------|
| SharedKernel | Traits, événements, exceptions |
| IdentityAccess | User, JWT, register/login/me |
| Catalog | Catégories, produits, variantes, unités |
| Inventory | Politiques, lots, mouvements, allocations |
| System | Health |

---

## API (aperçu)

- `POST /api/login_check`, `GET /api/me`, `GET /api/health`
- `GET|POST /api/categories`, `/api/products`, `/api/products/{id}/variants`
- `GET|POST /api/variants/{id}/lots`, `/stock-out`, `/stock-alerts`, `/stock-movements`

---

## Frontend

Configs magasin dans `mono/frontend/shop-configs/` :

```bash
npm run dev:shop -- --shop=default
npm run build:shop -- --shop=default --env=prod
```

Déploiement multi-boutiques = **une instance mono par boutique** (API + build frontend avec `--shop=<id>`).

---

## Suite SaaS

Quand le métier mono sera stable, il sera réintégré dans `saas/` (Account / Shop / memberships / plateforme).
