# Stockify User App

Application métier pour les utilisateurs de compte : sélection tenant, catalogue et gestion de stock par boutique.

## Prérequis

- Node.js >= 20.19
- API Stockify démarrée sur `http://localhost:8000`
- Fixtures chargées (compte demo + catalogue)

## Installation

```powershell
copy .env.example .env
npm install
```

## Développement

```powershell
npm run dev
```

Application : [http://localhost:5175](http://localhost:5175)

## Build production

```powershell
npm run build
npm run preview
```

## Configuration

| Variable | Défaut | Description |
|----------|--------|-------------|
| `VITE_API_URL` | `http://localhost:8000/api` | URL de base de l'API |

## Comptes de test

| Rôle | Email | Username | Mot de passe |
|------|-------|----------|--------------|
| Owner | `owner@demo.stockify.local` | `owner` | `Demo123!` |
| Manager | `manager@demo.stockify.local` | `manager` | `Demo123!` |

Après connexion, sélectionnez le compte **Demo Commerce** et une boutique sur le dashboard. Les requêtes métier envoient les headers `X-Account-Id` et `X-Shop-Id`.

## Écrans V1

**Catalogue**

- Catégories, produits, variantes (CRUD)

**Stock**

- Lots (réception), mouvements (sortie FIFO, ajustements), alertes seuil

## Flux de validation

1. Landing `/` → connexion
2. Dashboard : choix compte + boutique
3. Créer catégorie → produit → variante
4. Réceptionner un lot → sortie stock → consulter alertes et journal
