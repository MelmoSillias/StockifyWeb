<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">{{ shopConfig.brandName }}</p>
        <h1 class="dashboard-title">{{ shopConfig.displayName }}</h1>
        <p class="dashboard-description">
          {{ shopConfig.brandSubtitle }}
        </p>
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
        <template #title>Raccourcis</template>
        <template #content>
          <ul class="dashboard-feature-list">
            <li v-for="link in quickLinks" :key="link.title" class="dashboard-feature-list__item">
              <div>
                <p class="dashboard-feature-list__title">{{ link.title }}</p>
                <p class="dashboard-feature-list__text">{{ link.text }}</p>
              </div>
              <Button
                :label="link.action"
                icon="pi pi-arrow-right"
                icon-pos="right"
                text
                @click="router.push({ name: link.routeName })"
              />
            </li>
          </ul>
        </template>
      </Card>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'

import { appConfig } from '@/config/app'
import shopConfig from '@/shopConfig'

const router = useRouter()

const starterCards = computed(() => [
  {
    title: 'Magasin',
    value: shopConfig.displayName,
    hint: shopConfig.id,
    icon: 'pi pi-shop',
    iconClass: 'dashboard-kpi-card__icon--secondary'
  },
  {
    title: 'Contact',
    value: shopConfig.shopPhone || '—',
    hint: shopConfig.printProfile?.email || 'Coordonnées magasin',
    icon: 'pi pi-phone',
    iconClass: 'dashboard-kpi-card__icon--tertiary'
  },
  {
    title: 'Auth',
    value: appConfig.auth.enabled ? 'Activee' : 'Desactivee',
    hint: 'Session active',
    icon: 'pi pi-lock',
    iconClass: ''
  }
])

const nextSteps = [
  {
    title: 'Catégories',
    text: 'Structurez votre catalogue par familles.',
    action: 'Ouvrir',
    routeName: 'catalog-categories'
  },
  {
    title: 'Produits',
    text: 'Créez produits, variantes et gérez le stock.',
    action: 'Ouvrir',
    routeName: 'catalog-products'
  },
  {
    title: 'Mouvements',
    text: 'Consultez l’historique des entrées et sorties.',
    action: 'Voir',
    routeName: 'inventory-movements'
  }
]

const quickLinks = [
  {
    title: 'Produits',
    text: 'Parcourir et gérer le catalogue.',
    action: 'Ouvrir',
    routeName: 'catalog-products'
  },
  {
    title: 'Catégories',
    text: 'Familles de produits du magasin.',
    action: 'Ouvrir',
    routeName: 'catalog-categories'
  },
  {
    title: 'Mouvements',
    text: 'Historique des entrées et sorties.',
    action: 'Ouvrir',
    routeName: 'inventory-movements'
  }
]
</script>
