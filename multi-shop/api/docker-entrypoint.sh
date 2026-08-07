#!/bin/sh
set -e

resolve_key_path() {
  echo "$1" | sed 's|%kernel.project_dir%|/app|g'
}

echo "[stockify-api] Ensuring local JWT key pair..."
php bin/console lexik:jwt:generate-keypair --skip-if-exists 2>/dev/null || true

echo "[stockify-api] Syncing Control Plane public keys..."
if ! php scripts/sync-public-keys-from-control-plane.php; then
  echo "[stockify-api] WARNING: Public key sync failed. Login/integration may fail until keys are present." >&2
fi

IDENTITY_KEY=$(resolve_key_path "${IDENTITY_JWT_PUBLIC_KEY:-/app/config/jwt/identity_public.pem}")
INTEGRATION_KEY=$(resolve_key_path "${INTEGRATION_JWT_PUBLIC_KEY:-/app/config/jwt/integration_public.pem}")

missing=0

if [ ! -f "$IDENTITY_KEY" ]; then
  echo "[stockify-api] ERROR: Identity JWT public key not found at $IDENTITY_KEY" >&2
  missing=1
fi

if [ ! -f "$INTEGRATION_KEY" ]; then
  echo "[stockify-api] ERROR: Integration JWT public key not found at $INTEGRATION_KEY" >&2
  missing=1
fi

if [ "$missing" -eq 1 ]; then
  echo "[stockify-api] Ensure Admin SaaS is deployed first and CONTROL_PLANE_BASE_URL is reachable." >&2
fi

exec "$@"
