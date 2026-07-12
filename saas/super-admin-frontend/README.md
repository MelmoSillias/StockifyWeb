# Stockify Super Admin

Console plateforme pour les utilisateurs `ROLE_SUPER_ADMIN` : monitoring API, liste des comptes et boutiques.

## Prérequis

- Node.js >= 20.19
- API Stockify démarrée sur `http://localhost:8000`

## Installation

```powershell
copy .env.example .env
npm install
```

## Développement

```powershell
npm run dev
```

Application : [http://localhost:5174](http://localhost:5174)

## Build production

```powershell
npm run build
npm run preview
```

## Configuration

| Variable | Défaut | Description |
|----------|--------|-------------|
| `VITE_API_URL` | `http://localhost:8000/api` | URL de base de l'API |

## Compte de test

| Champ | Valeur |
|-------|--------|
| Email | `admin@stockify.local` |
| Username | `admin` |
| Mot de passe | `Admin123!` |

## Écrans V1

- **Dashboard** — santé API, nombre de comptes/boutiques
- **Comptes** — liste + détail (lecture seule)
- **Boutiques** — vue transverse

Les données proviennent des fixtures (`api/`) ou de l'API `GET /api/admin/*`.
