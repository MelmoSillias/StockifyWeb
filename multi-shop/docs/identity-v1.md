# Identity API v1 — Control Plane ↔ Stockify

Complément du contrat d'intégration pour l'authentification par identité globale (Gap C).

## Flux

1. Utilisateur → `POST /api/auth/global` (Data Plane)
2. Data Plane → `POST /api/identity/v1/token` (Control Plane, HMAC)
3. Control Plane vérifie `GlobalUser` + password, retourne assertion RS256
4. Data Plane valide l'assertion, résout `User` via `identity_id`, émet JWT Lexik local

## Endpoint Control Plane

```
POST /api/identity/v1/token
```

**Protection :** header `X-Integration-Signature` (HMAC-SHA256 du body avec `INTEGRATION_WEBHOOK_SECRET`).

**Body :**

```json
{
  "email": "owner@example.com",
  "password": "...",
  "application": "stockify"
}
```

**Réponse :**

```json
{
  "data": {
    "assertion": "<jwt RS256>"
  }
}
```

**Claims assertion :** `sub` (GlobalUser UUID), `aud` (application slug), `email`, `accounts[]`, `iss`, `iat`, `exp`.

## Endpoint Data Plane

```
POST /api/auth/global
```

Feature flag : `AUTH_GLOBAL_IDENTITY_ENABLED=true`

**Body :**

```json
{
  "email": "owner@example.com",
  "password": "..."
}
```

**Réponse :** `{ "token": "<jwt local>" }`

## Variables d'environnement

| Variable | Côté | Rôle |
|----------|------|------|
| `IDENTITY_JWT_PRIVATE_KEY` | CP | Signature assertion |
| `IDENTITY_JWT_PUBLIC_KEY` | DP | Validation assertion |
| `IDENTITY_JWT_ISSUER` | CP + DP | Claim `iss` |
| `IDENTITY_JWT_AUDIENCE` | DP | Claim `aud` attendu (`stockify`) |
| `IDENTITY_JWT_TTL_SECONDS` | CP | TTL assertion (défaut 300) |
| `AUTH_GLOBAL_IDENTITY_ENABLED` | DP | Active `/api/auth/global` |
| `INTEGRATION_WEBHOOK_SECRET` | CP + DP | HMAC DP→CP token exchange |

## Sync clé publique

```bash
php scripts/sync-identity-public-key.php
```

## Signup owner global

Wizard public LafiaSugu (3 étapes) → `POST /api/public/signup` (Data Plane) :

1. **Identité** — `firstName`, `lastName` (requis), `phone` (optionnel) → `users` local
2. **Connexion** — `adminEmail`, `adminPassword`
3. **Boutique** — `accountName`, `accountSlug` (requis), `shopPhone`, `shopAddress` (optionnels) → `shops` local

Le DP appelle ensuite `POST /api/public/signup` (CP) avec `adminEmail` + `adminPassword` : crée `GlobalUser` + `AccountMember` owner et retourne `identityId`. Le Data Plane persiste `users.identity_id` sur l'admin owner local. Le profil personne (prénom/nom/téléphone) n'est pas stocké sur `GlobalUser` en v1.
