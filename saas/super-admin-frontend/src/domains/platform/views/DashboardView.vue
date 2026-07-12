<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Plateforme</p>
        <h1 class="dashboard-title">Monitoring</h1>
        <p class="dashboard-description">
          Vue d'ensemble de la plateforme et de son état de fonctionnement.
        </p>
      </div>
    </div>

    <AppStatsCards :items="statsItems" />

    <Card class="dashboard-panel">
      <template #content>
        <AppTableState
          :loading="platformStore.loading"
          :is-empty="false"
        >
          <div class="health-row">
            <div>
              <p class="health-row__label">Santé API</p>
              <p class="health-row__value">
                <Tag
                  :value="healthLabel"
                  :severity="healthSeverity"
                  rounded
                />
              </p>
            </div>
            <p class="health-row__hint">
              Vérification automatique
            </p>
          </div>
        </AppTableState>
      </template>
    </Card>
  </section>
</template>

<script setup>
import { computed, onMounted } from 'vue'

import Card from 'primevue/card'
import Tag from 'primevue/tag'

import AppStatsCards from '@/domains/shared/components/AppStatsCards.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { usePlatformStore } from '@/domains/platform/stores/platform'

const platformStore = usePlatformStore()
const { showError } = useEntityActions()

const statsItems = computed(() => [
  {
    label: 'Comptes',
    value: platformStore.stats?.accounts_count ?? '—',
    hint: 'Comptes actifs',
    icon: 'pi pi-building'
  },
  {
    label: 'Boutiques',
    value: platformStore.stats?.shops_count ?? '—',
    hint: 'Points de vente actifs',
    icon: 'pi pi-shop'
  },
  {
    label: 'API',
    value: platformStore.health?.status === 'ok' ? 'OK' : '—',
    hint: 'Disponibilité',
    icon: 'pi pi-server'
  }
])

const healthLabel = computed(() => {
  if (platformStore.loading) {
    return 'Chargement...'
  }
  return platformStore.health?.status === 'ok' ? 'Opérationnel' : 'Indisponible'
})

const healthSeverity = computed(() => (
  platformStore.health?.status === 'ok' ? 'success' : 'danger'
))

onMounted(async () => {
  try {
    await platformStore.fetchDashboard()
  } catch (error) {
    showError(error?.message || 'Impossible de charger le dashboard.')
  }
})
</script>

<style scoped>
.health-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.health-row__label,
.health-row__value,
.health-row__hint {
  margin: 0;
}

.health-row__label {
  color: var(--p-text-muted-color);
  font-size: 0.9rem;
}

.health-row__value {
  margin-top: 0.35rem;
}

.health-row__hint {
  color: var(--p-text-muted-color);
  font-size: 0.85rem;
}
</style>
