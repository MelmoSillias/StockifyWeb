<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Compte</p>
        <h1 class="dashboard-title">{{ account?.name || 'Détail compte' }}</h1>
        <p class="dashboard-description">Détails du compte et boutiques associées.</p>
      </div>
      <Button label="Retour" icon="pi pi-arrow-left" text @click="router.push({ name: 'accounts' })" />
    </div>

    <AppTableState :loading="platformStore.detailLoading" :is-empty="!platformStore.detailLoading && !account">
      <Card v-if="account" class="dashboard-panel">
        <template #content>
          <dl class="detail-grid">
            <div><dt>Slug</dt><dd>{{ account.slug }}</dd></div>
            <div><dt>Statut</dt><dd><Tag :value="account.status" rounded /></dd></div>
            <div><dt>Devise</dt><dd>{{ account.default_currency }}</dd></div>
            <div><dt>Fuseau</dt><dd>{{ account.timezone }}</dd></div>
          </dl>
        </template>
      </Card>

      <Card v-if="account" class="dashboard-panel">
        <template #title>Boutiques</template>
        <template #content>
          <DataTable :value="account.shops || []" data-key="id" striped-rows responsive-layout="scroll">
            <Column field="name" header="Nom" />
            <Column field="slug" header="Slug" />
            <Column header="Statut">
              <template #body="{ data }">
                <Tag :value="data.status" rounded />
              </template>
            </Column>
            <Column field="currency" header="Devise" />
          </DataTable>
        </template>
      </Card>
    </AppTableState>
  </section>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'

import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { usePlatformStore } from '@/domains/platform/stores/platform'

const platformStore = usePlatformStore()
const route = useRoute()
const router = useRouter()
const { showError } = useEntityActions()

const account = computed(() => platformStore.currentAccount)

const loadAccount = async (id) => {
  try {
    await platformStore.fetchAccount(id)
  } catch (error) {
    showError(error?.message || 'Impossible de charger le compte.')
  }
}

onMounted(() => loadAccount(route.params.id))
watch(() => route.params.id, (id) => {
  if (id) {
    loadAccount(id)
  }
})
</script>

<style scoped>
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  margin: 0;
}

.detail-grid dt {
  margin: 0;
  color: var(--p-text-muted-color);
  font-size: 0.85rem;
}

.detail-grid dd {
  margin: 0.25rem 0 0;
  font-weight: 600;
}
</style>
