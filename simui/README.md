# SimUI — Modele frontend

Modele Vue 3 reutilisable pour lancer rapidement un frontend metier. Copiez ce dossier dans un nouveau projet et adaptez-le selon vos besoins.

## Contenu du modele

- Shell PrimeVue (sidebar, topbar, theming)
- Structure par domaines (`src/domains/`)
- Auth JWT optionnelle (desactivee par defaut)
- Client axios configurable
- Briques CRUD partagees (`domains/shared/`)
- Deux pages de depart : **Accueil** et **Documentation rapide**

## Installation

```sh
npm install
npm run dev
```

## Personnalisation

1. Modifier `src/config/app.js` — branding, navigation, auth, API
2. Ajouter vos domaines dans `src/domains/`
3. Declarer vos routes dans `src/router/index.js`

Consultez la page **Documentation** dans l'application pour le guide detaille.

## Structure

```
src/
├── config/           # configuration globale
├── domains/
│   ├── auth/         # connexion JWT
│   ├── home/         # page d'accueil
│   ├── docs/         # documentation rapide
│   ├── layout/       # shell applicatif
│   └── shared/       # composants et services transverses
├── lib/              # axios, router context
└── router/           # routes
```

## Utilisation dans un nouveau projet

```sh
# Copier le modele
cp -r simui/ ../mon-projet/frontend

# Puis personnaliser config/app.js, ajouter vos domaines et routes
```

## Build

```sh
npm run build
```
