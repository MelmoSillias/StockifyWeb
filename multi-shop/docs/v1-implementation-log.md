# Journal — Stockify mono

> **Règle de documentation** : toute modification du projet mono (API, frontend, schéma, comportement métier) **doit être consignée** dans ce journal avec date, périmètre et fichiers/doc touchés. Si la conception ou le modèle de données change, mettre à jour [v1-design.md](v1-design.md) et/ou [new-data-model.md](new-data-model.md) dans la même PR/commit.

Le journal SaaS détaillé reste dans [`saas/docs/v1-implementation-log.md`](../../saas/docs/v1-implementation-log.md).

---

## État actuel (mono)

| Élément | Statut |
|---------|--------|
| API sans Tenancy / Platform | fait |
| Routes plates `/api/categories`, `/products`, … | fait |
| API catalog + inventory | fait |
| API commerce V2 (Client, Commerce, Facturation, Paiement, Livraison) | fait |
| Migrations commerce (6 fichiers, juil.–août 2026) | fait |
| Event-driven facturation / paiements / livraisons | fait |
| Fixtures owner/manager + catalogue démo | fait |
| Frontend Clientèle + Commerce (7 routes) | fait |
| Frontend Finances (page onglets) | fait |
| Shop-configs (`dev:shop` / `build:shop`) | fait |
| Caisse / trésorerie (Finance V3) | fait |
| Fournisseurs (V4) | fait |
| Vues Lots / Alertes / Variantes routées | **à faire** |

---

## Comptes fixtures

| Email | Username | Mot de passe |
|-------|----------|--------------|
| `owner@stockify.local` | `owner` | `Demo123!` |
| `manager@stockify.local` | `manager` | `Demo123!` |

---

## Journal des changements

### 2026-08-02 — Fournisseur V4 : actions workflow frontend

- Journal fournisseur : création commande achat, confirmation, annulation, réception, création dette manuelle, annulation paiement
- Liste fournisseurs : bouton « Nouvelle commande achat » par ligne
- Composants : `CreateCommandeFournisseurDialog`, `ConfirmCommandeFournisseurDialog`, `CreateDetteFournisseurDialog`
- Service : `dettesService.cancelPaiement`

### 2026-08-02 — Fournisseur V4

- Migration `Version20260802100000` : `fournisseurs`, `dettes_fournisseur`, `paiements_fournisseur`
- Migration `Version20260802120000` : `commandes_fournisseur`, `fournisseur_id` sur `stock_lots`
- Module `Fournisseur` : CRUD, commandes achat (`ACH-`), dettes, paiements, listeners stock + Finance (`depense`)
- Extension Finance : `TransactionSourceType::PaiementFournisseur`
- Frontend : domaine `fournisseur/`, menu groupé, journal fournisseur, carnet de dettes
- Fixtures : `FournisseurFixture` (2 fournisseurs démo + dette manuelle)
- Tests intégration : `FournisseurFlowTest`
- Docs : `v1-design.md`, `new-data-model.md`, `v1-implementation-log.md`

### 2026-08-01 — Finance V3 (trésorerie)

- Migration `Version20260801180000` : tables `comptes`, `modes_de_paiement`, `transactions`
- Refactor `Paiement` : `mode_de_paiement_id` remplace l'enum `PaymentMethod`
- Module `Finance` : CRUD comptes / transactions / modes de paiement, listeners `PaiementEnregistre` / `PaiementAnnule`
- Seed : comptes Caisse + Compte bancaire, 5 modes de paiement
- Frontend : domaine `finance/`, route `/app/finances`, composable `usePaymentMethods` branché aux formulaires commerce
- Tests intégration : `FinanceFlowTest` (transaction auto + manuelle)
- Docs : `v1-design.md`, `new-data-model.md`, `v1-implementation-log.md`

### 2026-08-01 — Documentation alignée sur le commerce V2

- Mise à jour de `v1-design.md`, `new-data-model.md`, `v1-implementation-log.md`
- Création de `docs/README.md` avec règle de logging obligatoire
- Note legacy ajoutée dans `data-model-mvc.md`
- Mise à jour de `mono/README.md`

### 2026-08-01 — Bons de livraison + date de livraison

- Migration `Version20260801120000` : `delivery_date` sur commandes
- Migration `Version20260801140000` : tables `bons_livraison`, `bon_livraison_lines`
- Migration `Version20260801092422` : correction FK `bon_de_livraison_id`
- Module `Livraison` : création bon, déstockage partiel (`BonDeLivraisonEnvoye`), marquage livré
- Frontend : `BonLivraisonDialog`, tri commandes par date de livraison

### 2026-08-01 — Frontend commerce & clientèle

- Domaines `client/` et `commerce/` : panier, commandes, ventes, paiements
- Vues : Clientèle, Carnet de dettes, Journal client
- Composants : checkout, sélecteur acheteur, enregistrement paiement, détail vente/commande

### 2026-07-31 — Annulation vente, avoirs, soft-delete client

- Migration `Version20260731140000` : `cancelled_at` sur ventes, tables `avoirs` / `avoir_lines`
- Migration `Version20260731160000` : `deleted_at` sur clients (soft-delete)
- Event listeners : restock sur annulation, création avoir, annulation paiements

### 2026-07-20 — Commerce V2 (schéma initial)

- Migration `Version20260720120000` : clients, ventes, commandes, factures, paiements
- 5 modules API : Client, Commerce, Facturation, Paiement (+ Livraison en août)
- Architecture event-driven : factures auto, déstockage, créances, acomptes

### 2026-07-09 — Socle initial

- Migration `Version20260709095841` : catalog + inventory
- Modules Catalog, Inventory, IdentityAccess, System
- Fixtures démo, frontend shop-configs

---

## Prochaines étapes métier

- Routage vues inventaire (Lots, Alertes, Variantes)
- Réintégration dans le SaaS quand le périmètre mono sera stable
