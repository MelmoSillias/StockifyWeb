<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Stock</p>
        <h1 class="dashboard-title">Alertes</h1>
        <p class="dashboard-description">Produits en stock faible.</p>
      </div>
    </div>

    <Card class="dashboard-panel">
      <template #content>
        <AppTableState
          :loading="inventoryStore.loading"
          :is-empty="!inventoryStore.loading && inventoryStore.alerts.length === 0"
          empty-title="Aucune alerte"
          empty-text="Toutes les variantes sont au-dessus de leur seuil."
        >
          <DataTable :value="inventoryStore.alerts" data-key="variant_id" striped-rows responsive-layout="scroll">
            <Column field="sku" header="SKU" />
            <Column field="available" header="Stock disponible" />
            <Column field="alert_threshold" header="Seuil alerte" />
            <Column header="Écart">
              <template #body="{ data }">
                <Tag value="Sous seuil" severity="danger" rounded />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>
  </section>
</template>

<script setup>
import { onMounted } from 'vue'

import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'

import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useInventoryStore } from '@/domains/inventory/stores/inventory'

const inventoryStore = useInventoryStore()
const { showError } = useEntityActions()

onMounted(async () => {
  try {
    await inventoryStore.fetchAlerts()
  } catch (error) {
    showError(error?.message || 'Impossible de charger les alertes.')
  }
})
</script>
