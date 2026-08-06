# Seeds SQL — Stockify Multi-Shop

## Signup essentials (RBAC)

Fichier : [`seed-signup-essentials.sql`](seed-signup-essentials.sql)

Prérequis **obligatoires** avant de tester la création de comptes :

- 79 permissions (`PermissionCatalog`)
- 6 rôles système : `admin`, **`gerant`**, `caissier`, `magasinier`, `comptable`, `consultant`
- Liens `role_permissions` (le signup assigne le rôle `gerant` à l’admin boutique)
- Unités de mesure : `piece`, `kg`, `liter`, `carton`

Ne crée **pas** de `tenant_accounts` / `shops` / `users` — le flux signup les provisionne.

### Prérequis

1. Schéma migré : `php bin/console doctrine:migrations:migrate`
2. Control Plane seedé : `seed-dev.sql` (racine SAAS → DB `sim_saas_admin`)
3. MySQL / MariaDB, base `stockify_multishop`

### Import

```bash
mysql -h 127.0.0.1 -u root stockify_multishop < seeds/seed-signup-essentials.sql
```

Sous XAMPP (Windows) :

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -uroot --default-character-set=utf8mb4 stockify_multishop -e "SOURCE C:/Users/DELL/Desktop/Programmation/SAAS/StockifyWeb/multi-shop/api/seeds/seed-signup-essentials.sql"
```

### Régénérer

```bash
php seeds/generate-signup-seed.php
```

---

## Catalogue réaliste

Fichier : [`realistic_catalog_seed.sql`](realistic_catalog_seed.sql)

Contenu approximatif :
- **4** catégories (Boissons, Épicerie, Hygiène & Beauté, Snacks & Confiserie)
- **~8–14** produits par catégorie (~53 au total)
- **1–3** variantes par produit (~90)
- **1–3** lots par variante (~180)
- Mouvements d’entrée (achats) et de sortie (ventes / ajustements)

À utiliser **après** un signup réussi (sur une boutique existante), pas pour tester la création de comptes.
