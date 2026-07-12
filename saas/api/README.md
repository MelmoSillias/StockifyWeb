# Stockify API (Symfony)

Backend REST de Stockify V1 : authentification JWT, tenancy (comptes/boutiques), catalogue et inventaire.

## Prérequis

- PHP >= 8.2 (extensions `ctype`, `iconv`)
- Composer
- MySQL 8+ (ou SQLite pour un setup minimal — adapter `DATABASE_URL`)
- OpenSSL (génération des clés JWT)

## Installation rapide

```powershell
cd api
copy .env.example .env
# Éditer .env : DATABASE_URL, APP_SECRET, JWT_PASSPHRASE

composer install

# Clés JWT (une seule fois)
php bin/console lexik:jwt:generate-keypair

# Base de données
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
```

Depuis la racine du dépôt, le script PowerShell `scripts/bootstrap-api.ps1` enchaîne ces étapes.

## Démarrage (dev)

```powershell
cd api
php -S localhost:8000 -t public
```

Vérification : [http://localhost:8000/api/health](http://localhost:8000/api/health) → `{"status":"ok"}`

## Comptes de démonstration (fixtures)

| Rôle | Email | Username | Mot de passe |
|------|-------|----------|--------------|
| Super-admin | `admin@stockify.local` | `admin` | `Admin123!` |
| Owner demo | `owner@demo.stockify.local` | `owner` | `Demo123!` |
| Manager demo | `manager@demo.stockify.local` | `manager` | `Demo123!` |

Les fixtures provisionnent aussi le compte **Demo Commerce** avec deux boutiques et un catalogue de démonstration.

## Commandes utiles

```powershell
# Tests
php bin/phpunit

# Vider le cache
php bin/console cache:clear

# Recharger les fixtures (écrase les données)
php bin/console doctrine:fixtures:load --no-interaction

# Lister les routes API
php bin/console debug:router | findstr api
```

## Variables d'environnement

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | Connexion Doctrine (MySQL recommandé) |
| `APP_SECRET` | Secret Symfony |
| `CORS_ALLOW_ORIGIN` | Regex des origines front autorisées |
| `JWT_*` | Chemins et passphrase des clés JWT |

## Endpoints principaux

- `POST /api/login_check` — connexion (body : `email`, `password`)
- `GET /api/me` — profil + memberships
- `GET /api/health` — santé (public)
- `GET /api/admin/*` — monitoring plateforme (`ROLE_SUPER_ADMIN`)
- `GET/POST /api/shops/{shopId}/...` — catalogue & stock (headers `X-Account-Id`, `X-Shop-Id`)

Voir [docs/v1-design.md](../docs/v1-design.md) pour le détail du modèle et des permissions.

## Frontends associés

- `super-admin-frontend/` — console plateforme (port dev **5174**)
- `user-frontend/` — application métier boutique (port dev **5175**)

Configurer `VITE_API_URL=http://localhost:8000/api` dans chaque frontend.
