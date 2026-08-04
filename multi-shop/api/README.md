# Stockify API — mono (single shop)

Backend REST Symfony : authentification JWT, catalogue et inventaire pour **un seul magasin** par instance.

## Prérequis

- PHP >= 8.2
- Composer
- MySQL 8+ (ou SQLite — adapter `DATABASE_URL`)
- OpenSSL (clés JWT)

## Installation rapide

```powershell
cd mono\api
copy .env.example .env
# Éditer .env : DATABASE_URL, APP_SECRET, JWT_PASSPHRASE

composer install
php bin/console lexik:jwt:generate-keypair
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
```

Depuis `mono/`, le script `scripts/bootstrap-api.ps1` enchaîne ces étapes.

## Démarrage (dev)

```powershell
cd mono\api
php -S localhost:8000 -t public
```

Health : [http://localhost:8000/api/health](http://localhost:8000/api/health)

## Comptes de démonstration (fixtures)

| Rôle | Email | Username | Mot de passe |
|------|-------|----------|--------------|
| Owner | `owner@stockify.local` | `owner` | `Demo123!` |
| Manager | `manager@stockify.local` | `manager` | `Demo123!` |

## Endpoints principaux

- `POST /api/login_check` — connexion (`email`, `password`)
- `GET /api/me` — profil utilisateur
- `GET /api/health` — santé (public)
- `GET/POST /api/categories`, `/api/products`, `/api/variants/...` — catalogue
- `GET/POST /api/variants/{id}/lots`, `/stock-out`, `/stock-alerts` — inventaire

## Frontend associé

- `mono/frontend/` — application métier (configs shop via `npm run dev:shop -- --shop=default`)
