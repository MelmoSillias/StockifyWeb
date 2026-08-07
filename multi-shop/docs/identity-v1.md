# Identity API v1 — Control Plane ↔ Stockify

Complément du contrat d'intégration pour l'authentification par identité globale (Gap C).

## Modèle Control Plane

| Entité | Rôle |
|--------|------|
| `Identity` | Identité globale (UUID stable = `identity_id` DP) |
| `IdentityEmail` | E-mails (primary + `verified_at`) |
| `Credential` | Secret PASSWORD (hash unique côté CP) |
| `ExternalIdentity` | Liaison OAuth (scaffolding) |
| `AccountMembership` | Appartenance Identity ↔ Account |

`AdminUser` est une entité **distincte** (console plateforme uniquement).

## Flux login unifié (Data Plane)

1. Utilisateur → `POST /api/login_check` (Data Plane)
2. Si `users.identity_id` est renseigné : DP → `POST /api/identity/v1/token` (Control Plane, HMAC)
3. Control Plane vérifie `Identity` + `Credential`, retourne assertion RS256
4. Data Plane valide l'assertion, résout `User` via `identity_id`, émet `{ access_token, refresh_token }` + cookie HttpOnly
5. Si pas d'`identity_id` : auth locale via `users.password_hash`

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

**Claims assertion :** `sub` (Identity UUID), `aud` (application slug), `email`, `email_verified`, `auth_methods` (ex. `["password","google"]`), `accounts[]`, `iss`, `iat`, `exp`.

Le claim legacy `auth_provider` n'est plus émis ; le DP accepte les deux formats lors de la transition.

## Refresh token (Data Plane)

```
POST /api/token/refresh
```

Refresh token via cookie HttpOnly `stockify_refresh_token` ou body `{ "refresh_token": "..." }`.

**Réponse :** `{ "access_token": "...", "refresh_token": "..." }` (rotation à chaque refresh).

## Frontend tokens

- Access token : mémoire Pinia uniquement (pas de `localStorage`)
- Refresh : cookie HttpOnly + interceptor axios 401
- Permissions/features en `localStorage` : affichage UI seulement (non autoritatif)

## Password reset (Control Plane)

```
POST /api/public/identity/password-reset/request
POST /api/public/identity/password-reset/confirm
```

Les utilisateurs liés (`identity_id`) ne peuvent pas changer leur mot de passe côté DP (`POST /api/me/password` → 403).

## Variables d'environnement

| Variable | Côté | Rôle |
|----------|------|------|
| `IDENTITY_JWT_PRIVATE_KEY` | CP | Signature assertion |
| `IDENTITY_JWT_PUBLIC_KEY` | DP | Validation assertion |
| `IDENTITY_JWT_ISSUER` | CP + DP | Claim `iss` |
| `IDENTITY_JWT_AUDIENCE` | DP | Claim `aud` attendu (`stockify`) |
| `IDENTITY_JWT_TTL_SECONDS` | CP | TTL assertion (défaut 300) |
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

Le DP appelle ensuite `POST /api/public/signup` (CP) avec `adminEmail` + `adminPassword` : crée `Identity` + `AccountMembership` owner et retourne `identityId`. Le Data Plane persiste `users.identity_id` **sans** dupliquer le hash password local.

## Sync état identité (M2M)

```
PATCH /integration/v1/identities/{identityId}
```

Body : `{ "email_verified_at": "<ISO8601|null>" }` — met à jour le miroir DP `User.emailVerifiedAt`.
