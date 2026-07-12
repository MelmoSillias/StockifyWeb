<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">SimUI</p>
        <h1 class="dashboard-title">Une base neutre pour lancer un frontend metier.</h1>
        <p class="dashboard-description">
          Ce modele fournit le shell PrimeVue, la navigation, le theming, l'authentification optionnelle
          et une structure par domaines. Copiez-le, renommez la configuration et ajoutez vos modules metier.
        </p>
        <div class="dashboard-hero__meta">
          <Tag value="Vue 3" severity="contrast" rounded />
          <Tag value="PrimeVue 4" severity="info" rounded />
          <Tag value="Pinia + Router" severity="success" rounded />
        </div>
      </div>
    </div>

    <div class="dashboard-kpis">
      <Card v-for="item in starterCards" :key="item.title" class="dashboard-kpi-card">
        <template #content>
          <div class="dashboard-kpi-card__icon" :class="item.iconClass">
            <i :class="item.icon"></i>
          </div>
          <p class="dashboard-kpi-card__label">{{ item.title }}</p>
          <p class="dashboard-kpi-card__value">{{ item.value }}</p>
          <p class="dashboard-kpi-card__hint">{{ item.hint }}</p>
        </template>
      </Card>
    </div>

    <div class="dashboard-grid">
      <Card class="dashboard-panel">
        <template #title>Premiers pas</template>
        <template #content>
          <ul class="dashboard-feature-list">
            <li v-for="step in nextSteps" :key="step.title" class="dashboard-feature-list__item">
              <div>
                <p class="dashboard-feature-list__title">{{ step.title }}</p>
                <p class="dashboard-feature-list__text">{{ step.text }}</p>
              </div>
              <Button
                v-if="step.routeName"
                :label="step.action"
                icon="pi pi-arrow-right"
                icon-pos="right"
                text
                @click="router.push({ name: step.routeName })"
              />
            </li>
          </ul>
        </template>
      </Card>

      <Card class="dashboard-panel">
        <template #title>Structure du modele</template>
        <template #content>
          <ul class="dashboard-feature-list">
            <li v-for="item in structureItems" :key="item" class="dashboard-feature-list__item">
              <code class="dashboard-feature-list__text">{{ item }}</code>
            </li>
          </ul>
        </template>
      </Card>
    </div>
  </section>
</template>

<script setup>
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'

import { appConfig } from '@/config/app'

const router = useRouter()

const starterCards = [
  {
    title: 'Configuration',
    value: 'app.js',
    hint: 'Branding, navigation, auth et API',
    icon: 'pi pi-sliders-h',
    iconClass: 'dashboard-kpi-card__icon--secondary'
  },
  {
    title: 'Domaines',
    value: 'src/domains',
    hint: 'Views, stores, services par module',
    icon: 'pi pi-folder-open',
    iconClass: 'dashboard-kpi-card__icon--tertiary'
  },
  {
    title: 'Auth',
    value: appConfig.auth.enabled ? 'Activee' : 'Desactivee',
    hint: 'JWT configurable dans config/app.js',
    icon: 'pi pi-lock',
    iconClass: ''
  }
]

const nextSteps = [
  {
    title: 'Lire la documentation rapide',
    text: 'Comprendre comment copier le modele et ajouter vos premieres routes.',
    action: 'Ouvrir',
    routeName: 'docs'
  },
  {
    title: 'Personnaliser le branding',
    text: 'Modifier le nom, le tagline et la navigation dans src/config/app.js.',
    action: 'Voir la doc',
    routeName: 'docs'
  },
  {
    title: 'Ajouter un domaine metier',
    text: 'Creer un dossier domains/mon-module avec views, stores et services.',
    action: 'Voir la doc',
    routeName: 'docs'
  }
]

const structureItems = [
  'src/config/app.js',
  'src/domains/layout/',
  'src/domains/shared/',
  'src/domains/auth/',
  'src/domains/home/',
  'src/domains/docs/',
  'src/router/index.js'
]
</script>
