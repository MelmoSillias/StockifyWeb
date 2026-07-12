# Stockify

Gestion de stock pour boutique.

## Structure du dépôt

| Dossier | Rôle |
|---------|------|
| **`mono/`** | **Actif** — instance mono-magasin (API + frontend + docs) |
| `saas/` | **En pause** — version multi-tenant / multi-boutique |
| `mvc/` | Legacy Symfony MVC (référence) |
| `simui/` | Modèle UI de référence (ne pas déployer) |

## Démarrage rapide — mono (dev)

### 1. Backend

```powershell
.\mono\scripts\bootstrap-api.ps1
.\mono\scripts\start-api.ps1
```

API : [http://localhost:8000/api/health](http://localhost:8000/api/health)

### 2. Frontend

```powershell
cd mono\frontend
npm install
npm run dev
# → http://localhost:5175
```

Switcher de magasin (branding / URL API) :

```powershell
npm run dev:shop -- --shop=default
npm run build:shop -- --shop=default --env=prod
```

### Comptes de test

| Identifiant | Mot de passe |
|-------------|--------------|
| `owner@stockify.local` ou `owner` | `Demo123!` |
| `manager@stockify.local` ou `manager` | `Demo123!` |

## Documentation

- [Conception mono](mono/docs/v1-design.md)
- [Modèle de données](mono/docs/new-data-model.md)
- [Journal](mono/docs/v1-implementation-log.md)
- [README API](mono/api/README.md)
- [README frontend](mono/frontend/README.md)

## SaaS (pause)

Le code SaaS (tenancy, super-admin, headers multi-boutique) est sous `saas/`.  
Il sera repris après stabilisation du métier mono.
