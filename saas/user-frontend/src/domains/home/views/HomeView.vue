<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Stockify</p>
        <h1 class="dashboard-title">Espace boutique</h1>
        <p class="dashboard-description">
          Choisissez le compte et la boutique avec lesquels vous travaillez.
        </p>
        <div class="tenant-form">
          <div class="tenant-form__field">
            <label for="account">Compte</label>
            <Select
              id="account"
              :model-value="tenantStore.accountId"
              :options="accountOptions"
              option-label="label"
              option-value="value"
              placeholder="Choisir un compte"
              @update:model-value="onAccountChange"
            />
          </div>
          <div class="tenant-form__field">
            <label for="shop">Boutique</label>
            <Select
              id="shop"
              :model-value="tenantStore.shopId"
              :options="shopOptions"
              option-label="label"
              option-value="value"
              placeholder="Choisir une boutique"
              @update:model-value="onShopChange"
            />
          </div>
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
import { useTenantStore } from '@/domains/tenancy/stores/tenant'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Select from 'primevue/select'

import { appConfig } from '@/config/app'

const router = useRouter()
const tenantStore = useTenantStore()

const accountOptions = computed(() => tenantStore.accounts.map((account) => ({
  label: `${account.name} (${account.slug})`,
  value: account.id
})))

const shopOptions = computed(() => {
  const account = tenantStore.selectedAccount
  if (!account) {
    return []
  }

  return (account.shops || []).map((shop) => ({
    label: `${shop.name} (${shop.slug})`,
    value: shop.id
  }))
})

const onAccountChange = (accountId) => {
  const account = tenantStore.accounts.find((entry) => entry.id === accountId)
  const firstShopId = account?.shops?.[0]?.id || null
  tenantStore.setSelection(accountId, firstShopId)
}

const onShopChange = (shopId) => {
  tenantStore.setSelection(tenantStore.accountId, shopId)
}

const starterCards = computed(() => [
  {
    title: 'Compte actif',
    value: tenantStore.selectedAccount?.name || 'Aucun',
    hint: 'Compte sélectionné',
    icon: 'pi pi-sliders-h',
    iconClass: 'dashboard-kpi-card__icon--secondary'
  },
  {
    title: 'Boutique active',
    value: tenantStore.selectedShop?.name || 'Aucune',
    hint: 'Boutique sélectionnée',
    icon: 'pi pi-folder-open',
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
    title: 'Catalogue',
    text: 'Créez vos catégories, produits et variantes.',
    action: 'Ouvrir',
    routeName: 'catalog-categories'
  },
  {
    title: 'Stock',
    text: 'Réceptionnez vos lots et suivez les mouvements.',
    action: 'Ouvrir',
    routeName: 'inventory-lots'
  },
  {
    title: 'Alertes',
    text: 'Consultez les produits en stock faible.',
    action: 'Voir',
    routeName: 'inventory-alerts'
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
    title: 'Variantes',
    text: 'SKU, prix et seuils d\'alerte.',
    action: 'Ouvrir',
    routeName: 'catalog-variants'
  },
  {
    title: 'Mouvements',
    text: 'Historique des entrées et sorties.',
    action: 'Ouvrir',
    routeName: 'inventory-movements'
  }
]
</script>

<style scoped>
.tenant-form {
  margin-top: 1rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
  gap: 1rem;
}

.tenant-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.tenant-form__field label {
  font-size: var(--layout-font-size-sm);
  color: var(--layout-text-muted);
}
</style>
