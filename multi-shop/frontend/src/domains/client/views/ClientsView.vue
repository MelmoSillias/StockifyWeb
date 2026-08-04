<template>
  <section class="dashboard-page">
    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Clientèle"
          :count-label="`${filteredItems.length} client(s)`"
          create-label="Nouveau client"
          :show-create="hasPermission('client.clients.create')"
          :search-term="searchTerm"
          search-placeholder="Rechercher un client..."
          show-search
          @update:search-term="searchTerm = $event"
          @create="dialog.openCreate()"
          :reloading="clientsStore.loading"
          @reload="load"
        >
          <template #actions>
            <AppTablePrintExportBar table-type="clients" :search-term="searchTerm" />
          </template>
        </AppTablePanelHeader>
      </template>
      <template #content>
        <AppTableState
          :loading="clientsStore.loading"
          :error="clientsStore.error"
          :is-empty="!clientsStore.loading && filteredItems.length === 0"
          empty-title="Aucun client"
          empty-text="Créez un client pour le retrouver lors des ventes et commandes."
          @retry="load"
        >
          <DataTable
            :value="filteredItems"
            data-key="id"
            striped-rows
            :responsive-layout="tableLayout"
            paginator
            :rows="10"
          >
            <Column field="name" header="Nom" sortable />
            <Column v-if="!isMobile" header="Téléphone">
              <template #body="{ data }">{{ data.phone || '—' }}</template>
            </Column>
            <Column v-if="!isMobile" header="Email">
              <template #body="{ data }">{{ data.email || '—' }}</template>
            </Column>
            <Column header="Statut" style="width: 130px">
              <template #body="{ data }">
                <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" rounded />
              </template>
            </Column>
            <Column v-if="!isMobile" header="Plafond crédit" style="width: 140px">
              <template #body="{ data }">
                {{ data.credit_limit != null ? formatMoney(data.credit_limit) : '—' }}
              </template>
            </Column>
            <Column header="Actions" style="width: 210px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="clientRowActions(data)"
                  aria-label="Actions client"
                />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <AppCrudDialog
      :visible="dialog.visible"
      :model-value="dialog.formData"
      :title="dialog.mode === 'create' ? 'Nouveau client' : 'Modifier client'"
      subtitle="Coordonnées et paramètres du client."
      :fields="formFields"
      :loading="clientsStore.submitting"
      :general-error="clientsStore.error"
      @update:visible="dialog.visible = $event"
      @update:model-value="dialog.formData = $event"
      @submit="saveClient"
    />
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

import AppCrudDialog from '@/domains/shared/components/AppCrudDialog.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTablePrintExportBar from '@/domains/impression/components/AppTablePrintExportBar.vue'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useClientsStore } from '@/domains/client/stores/clients'
import { clientsService } from '@/domains/client/services/clientsService'
import { usePermissions } from '@/domains/auth/composables/usePermissions'

const router = useRouter()
const clientsStore = useClientsStore()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { hasPermission } = usePermissions()
const { formatMoney } = useDisplayFormatters()
const { showSuccess, showError, confirmRemoval } = useEntityActions()
const searchTerm = ref('')

const createEmptyForm = () => ({
  id: null,
  name: '',
  phone: '',
  email: '',
  credit_limit: null,
  status: 'active'
})

const dialog = useCrudDialog(createEmptyForm)

const statusOptions = [
  { label: 'Actif', value: 'active' },
  { label: 'Suspendu', value: 'suspended' }
]

const statusLabel = (status) => statusOptions.find((option) => option.value === status)?.label || status
const statusSeverity = (status) => (status === 'suspended' ? 'warn' : 'success')

const formFields = computed(() => [
  { name: 'name', label: 'Nom', type: 'text', placeholder: 'Ex: Jean Dupont', icon: 'pi pi-user' },
  { name: 'phone', label: 'Téléphone', type: 'text', placeholder: 'Ex: 06 12 34 56 78', icon: 'pi pi-phone' },
  { name: 'email', label: 'Email', type: 'text', placeholder: 'Ex: client@example.com', icon: 'pi pi-envelope' },
  {
    name: 'credit_limit',
    label: 'Plafond crédit',
    type: 'number',
    min: 0,
    placeholder: 'Facultatif',
    icon: 'pi pi-wallet'
  },
  {
    name: 'status',
    label: 'Statut',
    type: 'select',
    options: statusOptions,
    optionLabel: 'label',
    optionValue: 'value',
    icon: 'pi pi-info-circle'
  }
])

const filteredItems = computed(() => {
  const term = searchTerm.value.trim().toLowerCase()

  if (!term) {
    return clientsStore.items
  }

  return clientsStore.items.filter((client) =>
    [client.name, client.phone, client.email, client.status]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(term))
  )
})

const normalizeForEdit = (client) => ({
  ...createEmptyForm(),
  ...client,
  credit_limit: client.credit_limit != null ? Number(client.credit_limit) : null
})

const openJournal = (client) => {
  router.push({ name: 'client-journal', params: { id: client.id } })
}

const saveClient = async () => {
  try {
    await clientsStore.saveItem(dialog.formData)
    showSuccess('Client enregistré.')
    dialog.close()
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer le client.")
  }
}

const clientRowActions = (client) => [
  {
    label: 'Journal',
    icon: 'pi pi-book',
    command: () => openJournal(client)
  },
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    visible: hasPermission('client.clients.update'),
    command: () => dialog.openEdit(normalizeForEdit(client))
  },
  {
    label: 'Supprimer',
    icon: 'pi pi-trash',
    severity: 'danger',
    visible: hasPermission('client.clients.delete'),
    loading: clientsStore.isDeleting(client.id),
    command: () => confirmDelete(client)
  }
]

const confirmDelete = (client) => {
  confirmRemoval({
    header: 'Supprimer ce client ?',
    message: `Le client ${client.name} sera supprimé ou archivé s'il possède un historique.`,
    onAccept: async () => {
      try {
        const result = await clientsService.remove(client.id)
        clientsStore.removeLocalItem(client.id)
        showSuccess(
          result.mode === 'soft'
            ? 'Client archivé (historique conservé).'
            : 'Client supprimé.'
        )
      } catch (error) {
        showError(error?.message || 'Impossible de supprimer le client.')
      }
    }
  })
}

const load = async () => {
  try {
    await clientsStore.fetchAll()
  } catch (error) {
    showError(error?.message || 'Impossible de charger la clientèle.')
  }
}

onMounted(load)
</script>

<style scoped>
.actions-cell {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
</style>
