<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Tenancy</p>
        <h1 class="dashboard-title">Boutiques</h1>
        <p class="dashboard-description">Toutes les boutiques, tous comptes confondus.</p>
      </div>
    </div>

    <AppEntityToolbar
      :search-term="searchTerm"
      search-placeholder="Rechercher une boutique..."
      :count-label="`${filteredShops.length} boutique(s)`"
      :show-create="false"
      @update:search-term="searchTerm = $event"
    />

    <Card class="dashboard-panel">
      <template #content>
        <AppTableState
          :loading="platformStore.loading"
          :is-empty="!platformStore.loading && filteredShops.length === 0"
          empty-title="Aucune boutique"
          empty-text="Aucune boutique enregistrée pour le moment."
        >
          <DataTable :value="filteredShops" data-key="id" striped-rows responsive-layout="scroll">
            <Column field="name" header="Nom" />
            <Column field="slug" header="Slug" />
            <Column field="account_name" header="Compte" />
            <Column header="Statut">
              <template #body="{ data }">
                <Tag :value="data.status" rounded />
              </template>
            </Column>
            <Column field="currency" header="Devise" />
          </DataTable>
        </AppTableState>
      </template>
    </Card>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'

import AppEntityToolbar from '@/domains/shared/components/AppEntityToolbar.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { usePlatformStore } from '@/domains/platform/stores/platform'

const platformStore = usePlatformStore()
const { showError } = useEntityActions()
const searchTerm = ref('')

const filteredShops = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()
  if (!query) {
    return platformStore.shops
  }

  return platformStore.shops.filter((shop) => {
    const terms = [shop.name, shop.slug, shop.account_name, shop.status]
    return terms.some((value) => String(value || '').toLowerCase().includes(query))
  })
})

onMounted(async () => {
  try {
    await platformStore.fetchShops()
  } catch (error) {
    showError(error?.message || 'Impossible de charger les boutiques.')
  }
})
</script>
