<script setup>
import { computed, onMounted, ref } from 'vue'

import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'
import Paginator from 'primevue/paginator'
import Tag from 'primevue/tag'

import { profileService } from '@/domains/auth/services/profileService'
import { formatProfileDate, truncateUserAgent } from '@/domains/auth/composables/useUserDisplay'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const { showError } = useEntityActions()
const { isMobile } = useBreakpoint()

const loading = ref(false)
const items = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(10)

const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))

const first = computed(() => (page.value - 1) * limit.value)

const loadHistory = async () => {
  loading.value = true
  try {
    const response = await profileService.getLoginHistory({
      page: page.value,
      limit: limit.value
    })
    items.value = response.data || []
    total.value = response.meta?.total || 0
  } catch (error) {
    showError(error?.response?.data?.error || error?.message || 'Impossible de charger l\'historique de connexion.')
  } finally {
    loading.value = false
  }
}

const onPageChange = (event) => {
  page.value = event.page + 1
  loadHistory()
}

onMounted(loadHistory)
</script>

<template>
  <div class="login-history-panel">
    <div class="login-history-panel__intro">
      <h2>Historique de connexion</h2>
      <p>Les dernières sessions réussies enregistrées pour votre compte.</p>
    </div>

    <Message v-if="!loading && items.length === 0" severity="info">
      Aucune connexion enregistrée.
    </Message>

    <DataTable
      v-else
      :value="items"
      data-key="id"
      striped-rows
      :loading="loading"
      :responsive-layout="tableLayout"
    >
      <Column header="Date et heure" style="min-width: 11rem">
        <template #body="{ data }">
          {{ formatProfileDate(data.occurred_at) }}
        </template>
      </Column>
      <Column field="ip" header="Adresse IP" style="min-width: 8rem">
        <template #body="{ data }">
          {{ data.ip || '—' }}
        </template>
      </Column>
      <Column header="Navigateur" style="min-width: 12rem">
        <template #body="{ data }">
          <span :title="data.user_agent || undefined">
            {{ truncateUserAgent(data.user_agent) }}
          </span>
        </template>
      </Column>
      <Column header="Statut" style="width: 7rem">
        <template #body="{ data }">
          <Tag
            :value="data.status === 'success' ? 'Réussie' : data.status"
            :severity="data.status === 'success' ? 'success' : 'danger'"
            rounded
          />
        </template>
      </Column>
    </DataTable>

    <Paginator
      v-if="total > limit"
      :rows="limit"
      :total-records="total"
      :first="first"
      template="FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
      current-page-report-template="{first} - {last} sur {totalRecords}"
      class="login-history-panel__paginator"
      @page="onPageChange"
    />
  </div>
</template>

<style scoped>
.login-history-panel {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-width: 0;
}

.login-history-panel__intro h2 {
  margin: 0 0 0.25rem;
  font-size: 1.125rem;
}

.login-history-panel__intro p {
  margin: 0;
  color: var(--text-color-secondary, #64748b);
  font-size: 0.875rem;
}

.login-history-panel__paginator {
  justify-content: flex-end;
}

@media (max-width: 767px) {
  .login-history-panel__paginator {
    justify-content: center;
  }
}

@media (max-width: 360px) {
  .login-history-panel {
    gap: 0.75rem;
  }
}
</style>
