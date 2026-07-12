# Modèle de données — Stockify mono

Une instance = un magasin. Pas de tables `accounts` / `shops` / memberships.

Référence legacy : [data-model-mvc.md](data-model-mvc.md).  
Conception : [v1-design.md](v1-design.md).

---

## Domaines

| Domaine | Entités |
|---------|---------|
| IdentityAccess | `User`, `RefreshToken` |
| Catalog | `UnitOfMeasure`, `ProductCategory`, `Product`, `ProductVariant` |
| Inventory | `StockPolicy`, `StockLot`, `StockMovement`, `StockLotAllocation` |

---

## Catalogue

- **ProductCategory** : arborescence optionnelle (`parent`)
- **Product** : nom, référence, description, catégorie optionnelle
- **ProductVariant** : SKU unique, UoM, mode de vente, prix, seuil d’alerte
- **UnitOfMeasure** : référentiel global (pièce, kg, …)

## Inventaire

- **StockPolicy** : une stratégie par variante (FIFO par défaut)
- **StockLot** : quantité initiale / restante, coût, dates
- **StockMovement** : entrée / sortie + allocations de lots
- Stock disponible = somme des `quantity_remaining` des lots

---

## Différence avec le SaaS (en pause)

Le SaaS (`saas/docs/`) ajoute `Account`, `Shop`, `account_id` / `shop_id` sur les entités métier, et les headers `X-Account-Id` / `X-Shop-Id`. Le mono n’a pas ces concepts : le scoping est l’instance déployée.
