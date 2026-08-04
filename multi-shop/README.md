# Stockify multi-shop

Version expérimentale multi-boutique (OWNER + Shop, isolation par `shop_id`).

Le développement actif mono-magasin reste dans [`../mono/`](../mono/).

## Démarrage rapide (dev)

### Backend

```powershell
.\multi-shop\scripts\bootstrap-api.ps1
.\multi-shop\scripts\start-api.ps1
```

API : [http://localhost:8001/api/health](http://localhost:8001/api/health)

Variable d'environnement recommandée pour l'onboarding SaaS :

```env
CONTROL_PLANE_BASE_URL=http://localhost:8030
```

### Frontend

```powershell
cd multi-shop\frontend
npm install
npm run dev
# → http://localhost:5176
```

### Compte OWNER de test

| Identifiant | Mot de passe |
|-------------|--------------|
| `owner@stockify.local` | `Demo123!` |

## Documentation

- [Conception multi-shop](docs/multi-shop-design.md)
- [Instructions agents](../AGENTS.md)
