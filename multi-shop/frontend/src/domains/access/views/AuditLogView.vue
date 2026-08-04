<template>
  <section class="dashboard-page">
    <Card class="dashboard-panel">
      <template #title>
        <div class="audit-header">
          <div>
            <h2>Journal d'audit</h2>
            <p>{{ meta.total }} entrée(s)</p>
          </div>
          <div class="audit-filters">
            <InputText v-model="filters.action" placeholder="Action..." @keyup.enter="load" />
            <Button icon="pi pi-search" label="Filtrer" @click="load" />
            <Button
              icon="pi pi-refresh"
              text
              rounded
              severity="secondary"
              :loading="loading"
              aria-label="Actualiser"
              v-tooltip.top="'Actualiser'"
              @click="load()"
            />
          </div>
        </div>
      </template>
      <template #content>
        <AppTableState :loading="loading" :error="error" :is-empty="!loading && items.length === 0" empty-title="Aucune entrée" @retry="load()">
          <DataTable :value="items" data-key="id" striped-rows responsive-layout="scroll" paginator :rows="meta.limit" :total-records="meta.total" lazy @page="onPage">
            <Column field="occurred_at" header="Date" style="width: 180px">
              <template #body="{ data }">{{ formatDate(data.occurred_at) }}</template>
            </Column>
            <Column field="user_email" header="Utilisateur">
              <template #body="{ data }">{{ data.user_email || '—' }}</template>
            </Column>
            <Column field="action" header="Action" />
            <Column field="method" header="Méthode" style="width: 90px" />
            <Column header="Statut" style="width: 100px">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="data.status === 'success' ? 'success' : 'danger'" rounded />
              </template>
            </Column>
            <Column field="ip" header="IP" style="width: 130px" />
          </DataTable>
        </AppTableState>
      </template>
    </Card>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'

import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { accessService } from '@/domains/access/services/accessService'

const items = ref([])
const loading = ref(false)
const error = ref(null)
const filters = reactive({ action: '' })
const meta = reactive({ total: 0, page: 1, limit: 50 })

const formatDate = (value) => {
  if (!value) return '—'
  return new Date(value).toLocaleString('fr-FR')
}

const load = async (page = 1) => {
  loading.value = true
  error.value = null
  meta.page = page
  try {
    const response = await accessService.listAuditLogs({
      action: filters.action || undefined,
      page,
      limit: meta.limit
    })
    items.value = response.data ?? []
    meta.total = response.meta?.total ?? items.value.length
  } catch (err) {
    error.value = err?.message || "Impossible de charger le journal d'audit."
  } finally {
    loading.value = false
  }
}

const onPage = (event) => {
  load(event.page + 1)
}

onMounted(() => load())
</script>

<style scoped>
.audit-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}
.audit-header h2 { margin: 0; font-size: 1.1rem; }
.audit-header p { margin: 0.25rem 0 0; color: var(--p-text-muted-color); font-size: 0.9rem; }
.audit-filters { display: flex; gap: 0.5rem; align-items: center; }
</style>
