<template>
  <section class="dashboard-page">
    <div class="dashboard-grid">
      <Card v-for="section in sections" :key="section.title" class="dashboard-panel">
        <template #title>{{ section.title }}</template>
        <template #content>
          <p v-if="section.intro" class="dashboard-feature-list__text">{{ section.intro }}</p>
          <pre v-if="section.code" class="docs-snippet">{{ section.code }}</pre>
          <ul v-if="section.items?.length" class="dashboard-feature-list">
            <li v-for="item in section.items" :key="item" class="dashboard-feature-list__item">
              <span class="dashboard-feature-list__text">{{ item }}</span>
            </li>
          </ul>
        </template>
      </Card>
    </div>
  </section>
</template>

<script setup>
import Card from 'primevue/card'

import { appConfig } from '@/config/app'

const sections = [
  {
    title: '1. Copier le modele',
    intro: 'Dupliquez le dossier simui/ a la racine de votre nouveau projet, renommez-le (ex. frontend/) puis installez les dependances.',
    code: 'cp -r simui/ ../mon-projet/frontend\ncd ../mon-projet/frontend\nnpm install\nnpm run dev'
  },
  {
    title: '2. Personnaliser la configuration',
    intro: 'Le fichier src/config/app.js centralise le branding, la navigation, l\'auth et l\'URL API.',
    code: `export const appConfig = {
  branding: {
    name: 'MonApp',
    shortName: 'MA',
    tagline: 'Mon produit interne'
  },
  navigation: {
    items: [
      { key: 'home', label: 'Accueil', icon: 'pi pi-home', routeName: 'home' }
    ]
  },
  auth: { enabled: true, mode: 'jwt' },
  storage: {
    layoutPreferencesKey: 'monapp-layout-preferences',
    authTokenKey: 'monapp-access-token',
    authUserKey: 'monapp-auth-user'
  }
}`
  },
  {
    title: '3. Ajouter une route',
    intro: 'Declarez vos routes dans src/router/index.js et ajoutez l\'entree correspondante dans navigation.items.',
    code: `{
  path: 'commandes',
  name: 'orders',
  component: () => import('@/domains/orders/views/OrdersView.vue'),
  meta: { title: 'Commandes', section: 'Operations', layoutKey: 'orders' }
}`
  },
  {
    title: '4. Creer un domaine metier',
    items: [
      'domains/mon-module/views/ — pages Vue',
      'domains/mon-module/stores/ — etat Pinia',
      'domains/mon-module/services/ — appels API',
      'domains/mon-module/components/ — composants specifiques (optionnel)',
      'domains/shared/ — briques CRUD transverses deja fournies'
    ]
  },
  {
    title: '5. Authentification JWT',
    intro: `Auth actuellement ${appConfig.auth.enabled ? 'activee' : 'desactivee'}. Activez-la dans config/app.js et branchez votre backend.`,
    code: `auth: {
  enabled: true,
  mode: 'jwt',
  loginEndpoint: '/login_check',
  meEndpoint: '/auth/me',
  redirectOn401: true
}`
  },
  {
    title: '6. Variables d\'environnement',
    intro: 'Creez un fichier .env.local a la racine du frontend pour surcharger l\'URL API.',
    code: 'VITE_API_URL=http://localhost:8000/api'
  }
]
</script>

<style scoped>
.docs-snippet {
  margin: 0.75rem 0 0;
  padding: 1rem;
  border-radius: var(--layout-radius-md);
  background: color-mix(in srgb, var(--layout-panel-bg) 90%, transparent);
  border: 1px solid var(--layout-panel-border);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: var(--layout-font-size-sm);
  line-height: 1.5;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
