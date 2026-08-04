# Modèle de données — Stockify mono

Une instance = un magasin. Pas de tables `accounts` / `shops` / memberships.

Référence legacy : [data-model-mvc.md](data-model-mvc.md).  
Conception : [v1-design.md](v1-design.md).

---

## Domaines

| Domaine | Entités |
|---------|---------|
| IdentityAccess | `User`, `RefreshToken` |
| AccessAudit | `Role`, `Permission`, `UserRole`, `UserPermission`, `AuditLog` |
| Catalog | `UnitOfMeasure`, `ProductCategory`, `Product`, `ProductVariant` |
| Inventory | `StockPolicy`, `StockLot`, `StockMovement`, `StockLotAllocation` |
| Client | `Client` |
| Commerce | `Vente`, `VenteLine`, `Commande`, `CommandeLine` |
| Facturation | `Facture`, `FactureLine`, `Avoir`, `AvoirLine` |
| Paiement | `Paiement` |
| Finance | `Compte`, `ModeDePaiement`, `Transaction` |
| Livraison | `BonDeLivraison`, `BonDeLivraisonLine` |
| Fournisseur | `Fournisseur`, `CommandeFournisseur`, `CommandeFournisseurLine`, `DetteFournisseur`, `PaiementFournisseur` |

**35 entités** au total. Les relations intra-module utilisent des FK Doctrine ; les références inter-modules sont des colonnes UUID sans FK.

---

## Catalogue

- **ProductCategory** : arborescence optionnelle (`parent`), statut (`active` / `archived`)
- **Product** : nom, référence, description, catégorie optionnelle, statut
- **ProductVariant** : SKU unique, UoM, mode de vente, prix, seuil d'alerte, statut
- **UnitOfMeasure** : référentiel global (pièce, kg, …)

---

## Inventaire

- **StockPolicy** : une stratégie par variante (FIFO par défaut, LIFO, FEFO)
- **StockLot** : quantité initiale / restante, coût, dates
- **StockMovement** : entrée / sortie + allocations de lots (`StockLotAllocation`)
- Stock disponible = somme des `quantity_remaining` des lots

---

## Client

- **Client** : nom, téléphone, email, statut (`active` / `suspended`), plafond crédit (`credit_limit`), soft-delete (`deleted_at`)
- Pas de FK vers Commerce/Facturation : les ventes et commandes référencent `client_id` en UUID

---

## Commerce

### Vente (vente immédiate)

- **Vente** : référence auto (`VNT-…`), acheteur (`client_id` ou `anonymous_info`), montant total, `cancelled_at` optionnel
- **VenteLine** : variante, libellé, quantité, prix unitaire, total ligne
- Création → événement `VenteRealisee` → facture auto + déstockage immédiat

### Commande (vente différée)

- **Commande** : référence auto (`CMD-…`), acheteur, statut, montant total, acompte reçu (`deposit_received`), dates (`confirmed_at`, `cancelled_at`, `delivery_date`)
- **CommandeLine** : variante, libellé, quantité, prix unitaire, total ligne
- Statuts : `initiee` → `confirmee` → `partiellement_livree` → `livree` | `annulee`
- Confirmation → événement `CommandeConfirmee` → facture auto (stock décrémenté à la livraison, pas à la confirmation)

### Acheteur (value object)

Chaque vente ou commande a un acheteur :
- **Client enregistré** : `client_id` (UUID)
- **Anonyme** : `anonymous_info` (texte libre, ex. « Client de passage »)

---

## Facturation

- **Facture** : numéro auto (`FCT-…`), source (`vente_id` ou `commande_id`), référence source, acheteur, montant total, `issued_at`
- **FactureLine** : copie des lignes de l'opération source
- **Immuabilité** : une facture émise n'est jamais modifiée ni supprimée
- **Créance** : `is_creance`, `is_creance_finalized`, `credit_closed_at` — le solde est calculé à partir des paiements
- **Avoir** : note de crédit liée à une facture et une vente annulée (`Avoir` + `AvoirLine`)

---

## Paiement

- **Paiement** : référence auto, montant, `mode_de_paiement_id`, `paid_at`, `cancelled_at`
- Lié à une **facture** (règlement créance) ou à une **commande** (acompte)
- Annulation logique (`cancelled_at`), jamais de suppression physique
- L'API accepte encore `method` (slug) en alias transitoire ; le champ canonique est `mode_de_paiement_id`

---

## Finance

### Compte

- **Compte** : nom, type (`caisse` / `banque`), `is_default`, `is_active`
- Deux comptes seedés à l'installation : **Caisse** (défaut) et **Compte bancaire**
- Solde calculé : `SUM(revenu) - SUM(depense)` sur transactions non annulées

### ModeDePaiement

- **ModeDePaiement** : `code` (slug unique), `label`, `compte_id`, `is_active`, `generates_transaction`
- Cinq modes seedés : `cash`, `mobile_money`, `card`, `transfer`, `credit`
- Routage : espèces / mobile money → Caisse ; carte / virement → Compte bancaire
- Le mode `credit` ne génère pas de transaction (créance gérée par Facturation)

### Transaction

- **Transaction** : `compte_id`, type (`revenu` / `depense`), montant, libellé, `occurred_at`
- **Source** : `source_type` (`paiement` / `manuel`), `source_id` (UUID nullable)
- Paiements commerce → transaction `revenu` auto via listener `PaiementEnregistre`
- Annulation paiement → annulation logique de la transaction liée

---

## Livraison

- **BonDeLivraison** : référence auto, `commande_id`, statut (`envoye` / `delivre`), `sent_at`, `delivered_at`
- **BonDeLivraisonLine** : variante, libellé, quantité livrée
- Création → événement `BonDeLivraisonEnvoye` → déstockage partiel selon les lignes du bon
- Permet le suivi du **reste à livrer** par commande

---

## Événements domaine

| Événement | Effets |
|-----------|--------|
| `ProductVariantCreated` | Créer politique FIFO par défaut |
| `VenteRealisee` | Créer facture ; décrémenter stock |
| `VenteAnnulee` | Restock ; annuler paiements ; créer avoir |
| `CommandeConfirmee` | Créer facture |
| `CommandeAnnulee` | Restock réservations |
| `BonDeLivraisonEnvoye` | Décrémenter stock (livraison partielle) |
| `PaiementEnregistre` | Clore créance ; enregistrer acompte commande ; créer transaction revenu |
| `PaiementAnnule` | Rouvrir créance ; annuler transaction liée |

Listeners dans `mono/api/src/*/Application/EventListener/`.

---

## Fournisseur

- **Fournisseur** : nom, téléphone, email, statut (`active` / `suspended`), soft-delete (`deleted_at`)
- **CommandeFournisseur** : référence auto (`ACH-…`), `fournisseur_id`, statut, montant total, dates
- **CommandeFournisseurLine** : variante, libellé, quantité, coût unitaire
- **DetteFournisseur** : référence auto (`DET-…`), `fournisseur_id`, montant total, solde calculé via paiements
- **PaiementFournisseur** : référence auto (`DEC-…`), règlement d'une dette → transaction `depense` auto

Cycle achat : `initiee` → `confirmee` → `recue` | `annulee`. Réception totale → lots stock + dette si reste dû.

---

## Correspondance vs MVC legacy

| MVC | Mono V2 |
|-----|---------|
| `Produit` + `stock_actuel` (dénormalisé) | `Product` + `ProductVariant` + lots |
| `LotProduit` | `StockLot` + `StockLotAllocation` |
| `MouvementStock` | `StockMovement` |
| `Vente` / `DetailVente` | `Vente` / `VenteLine` |
| Client en texte libre | Entité `Client` dédiée |
| `CreditClient` + `PaiementCreditClient` | Créances via `Facture.is_creance` + `Paiement` |
| `TransactionCaisse` | `Transaction` + hub `Compte` / `ModeDePaiement` |
| `CreanceFournisseur` / fournisseurs | `Fournisseur` + `DetteFournisseur` + `PaiementFournisseur` + `CommandeFournisseur` |

---

## Différence avec le SaaS (en pause)

Le SaaS (`saas/docs/`) ajoute `Account`, `Shop`, `account_id` / `shop_id` sur les entités métier, et les headers `X-Account-Id` / `X-Shop-Id`. Le mono n'a pas ces concepts : le scoping est l'instance déployée.
