<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Tenancy</p>
        <h1 class="dashboard-title">Comptes</h1>
        <p class="dashboard-description">Tous les comptes clients de la plateforme.</p>
      </div>
    </div>

    <AppEntityToolbar
      :search-term="searchTerm"
      search-placeholder="Rechercher un compte..."
      :count-label="`${filteredAccounts.length} compte(s)`"
      :show-create="false"
      @update:search-term="searchTerm = $event"
    />

    <Card class="dashboard-panel">
      <template #content>
        <AppTableState
          :loading="platformStore.loading"
          :is-empty="!platformStore.loading && filteredAccounts.length === 0"
          empty-title="Aucun compte"
          empty-text="Aucun compte enregistré pour le moment."
        >
          <DataTable
            :value="filteredAccounts"
            data-key="id"
            striped-rows
            responsive-layout="scroll"
            @row-click="openAccount($event.data)"
          >
            <Column field="name" header="Nom" />
            <Column field="slug" header="Slug" />
            <Column header="Statut">
              <template #body="{ data }">
                <Tag :value="data.status" rounded />
              </template>
            </Column>
            <Column field="shops_count" header="Boutiques" />
            <Column header="" style="width: 80px">
              <template #body="{ data }">
                <Button icon="pi pi-arrow-right" text rounded @click.stop="openAccount(data)" />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'

import AppEntityToolbar from '@/domains/shared/components/AppEntityToolbar.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { usePlatformStore } from '@/domains/platform/stores/platform'

const platformStore = usePlatformStore()
const router = useRouter()
const { showError } = useEntityActions()
const searchTerm = ref('')

const filteredAccounts = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()
  if (!query) {
    return platformStore.accounts
  }

  return platformStore.accounts.filter((account) => {
    const terms = [account.name, account.slug, account.status]
    return terms.some((value) => String(value || '').toLowerCase().includes(query))
  })
})

const openAccount = (account) => {
  router.push({ name: 'account-detail', params: { id: account.id } })
}

onMounted(async () => {
  try {
    await platformStore.fetchAccounts()
  } catch (error) {
    showError(error?.message || 'Impossible de charger les comptes.')
  }
})
</script>
