# Seeds SQL — Stockify mono

## Catalogue réaliste

Fichier : [`realistic_catalog_seed.sql`](realistic_catalog_seed.sql)

Contenu approximatif :
- **4** catégories (Boissons, Épicerie, Hygiène & Beauté, Snacks & Confiserie)
- **~8–14** produits par catégorie (~53 au total)
- **1–3** variantes par produit (~90)
- **1–3** lots par variante (~180)
- Mouvements d’entrée (achats) et de sortie (ventes / ajustements) du **2026-04-11** au **2026-07-11**
- Allocations lot ↔ mouvement cohérentes (FIFO)
- Quelques variantes volontairement sous seuil d’alerte

### Prérequis

- Schéma migré (`php bin/console doctrine:migrations:migrate`)
- MySQL / MariaDB, base `stockify_mono`
- Les utilisateurs existants sont **conservés** (seules les tables catalogue + stock sont vidées)

### Import

```bash
mysql -uroot --default-character-set=utf8mb4 stockify_mono < seeds/realistic_catalog_seed.sql
```

Sous XAMPP (Windows) :

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -uroot --default-character-set=utf8mb4 stockify_mono -e "SOURCE C:/chemin/vers/mono/api/seeds/realistic_catalog_seed.sql"
```

### Régénérer le fichier

```bash
node scripts/generate-realistic-seed.mjs
```
