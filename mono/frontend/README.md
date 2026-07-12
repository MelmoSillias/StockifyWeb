# Stockify frontend — mono

Application Vue pour **un seul magasin**. Les infos magasin (nom, API, branding) sont centralisées dans `shop-configs/`.

## Démarrage

```powershell
cd mono\frontend
npm install
npm run dev
# → http://localhost:5175 (shop=default, env=dev)
```

## Switcher de magasin (déploiement / tests)

```powershell
npm run dev:shop -- --shop=default
npm run build:shop -- --shop=default --env=prod
```

Chaque magasin a son dossier :

```
shop-configs/<shop-id>/
  config.dev.json
  config.prod.json
  public/          # logo, favicon, …
```

Le script `select-shop.mjs` génère `src/generated/shop-config.generated.js` et synchronise les assets vers `public/` avant Vite.

## Compte de démo (API fixtures)

- Email : `owner@stockify.local` / username : `owner`
- Mot de passe : `Demo123!`
