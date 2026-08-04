# Documentation — Stockify mono

Documentation du socle métier **une instance = un magasin**. Le multi-tenant SaaS est en pause sous `saas/`.

---

## Règle de documentation

> **Toute modification** du projet mono (API, frontend, schéma, comportement métier) **doit être consignée** dans [v1-implementation-log.md](v1-implementation-log.md) avec date, périmètre et fichiers/doc touchés.
>
> Si la conception ou le modèle de données change, mettre à jour [v1-design.md](v1-design.md) et/ou [new-data-model.md](new-data-model.md) dans la même PR/commit.

---

## Fichiers

| Fichier | Rôle |
|---------|------|
| [v1-design.md](v1-design.md) | Vision, modules, API, frontend, flux commerce |
| [new-data-model.md](new-data-model.md) | Entités, relations, événements domaine, mapping vs MVC |
| [data-model-mvc.md](data-model-mvc.md) | Référence legacy (app MVC) — migration |
| [v1-implementation-log.md](v1-implementation-log.md) | Statut d'avancement + **journal des changements** |

Le journal SaaS détaillé reste dans [`saas/docs/v1-implementation-log.md`](../../saas/docs/v1-implementation-log.md).

---

## Ordre de lecture

1. **v1-design.md** — comprendre l'architecture et le périmètre fonctionnel
2. **new-data-model.md** — détail des entités et flux métier
3. **data-model-mvc.md** — baseline legacy pour comparaison / migration
4. **v1-implementation-log.md** — état actuel et historique des évolutions
