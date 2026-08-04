<template>
  <Card class="dashboard-panel">
    <template #title>
      <AppTablePanelHeader
        title="Comptes"
        :count-label="`${accountsStore.items.length} compte(s)`"
        create-label="Nouveau compte"
        show-create
        :reloading="accountsStore.loading"
        @create="dialog.openCreate()"
        @reload="accountsStore.fetchAll"
      />
    </template>
    <template #content>
      <AppTableState
        :loading="accountsStore.loading"
        :error="accountsStore.error"
        :is-empty="!accountsStore.loading && accountsStore.items.length === 0"
        empty-title="Aucun compte"
        empty-text="Créez un compte pour suivre votre trésorerie."
        @retry="accountsStore.fetchAll"
      >
        <DataTable
          :value="accountsStore.items"
          data-key="id"
          striped-rows
          :responsive-layout="tableLayout"
          paginator
          :rows="10"
        >
          <Column field="name" header="Nom" sortable />
          <Column v-if="!isMobile" header="Type" style="width: 120px">
            <template #body="{ data }">{{ accountTypeLabel(data.type) }}</template>
          </Column>
          <Column header="Solde" style="width: 140px">
            <template #body="{ data }">{{ formatMoney(data.balance) }}</template>
          </Column>
          <Column v-if="!isMobile" header="Statut" style="width: 120px">
            <template #body="{ data }">
              <Tag
                :value="data.is_active ? 'Actif' : 'Inactif'"
                :severity="data.is_active ? 'success' : 'secondary'"
                rounded
              />
            </template>
          </Column>
          <Column header="Actions" style="width: 90px">
            <template #body="{ data }">
              <AppTableActionsMenu
                :actions="accountRowActions(data)"
                aria-label="Actions compte"
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
    :title="dialog.mode === 'create' ? 'Nouveau compte' : 'Modifier compte'"
    subtitle="Compte de trésorerie (caisse ou banque)."
    :fields="formFields"
    :loading="accountsStore.submitting"
    :general-error="accountsStore.error"
    @update:visible="dialog.visible = $event"
    @update:model-value="dialog.formData = $event"
    @submit="saveAccount"
  />
</template>

<script setup>
import { computed } from 'vue'

import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'

import AppCrudDialog from '@/domains/shared/components/AppCrudDialog.vue'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { accountTypeLabel } from '@/domains/finance/composables/useFinanceFilters'
import { useAccountsStore } from '@/domains/finance/stores/accounts'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const emit = defineEmits(['refresh'])

const accountsStore = useAccountsStore()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { formatMoney } = useDisplayFormatters()
const { showSuccess, showError } = useEntityActions()

const createEmptyForm = () => ({
  id: null,
  name: '',
  type: 'caisse',
  is_active: true
})

const dialog = useCrudDialog(createEmptyForm)

const typeOptions = [
  { label: 'Caisse', value: 'caisse' },
  { label: 'Banque', value: 'banque' }
]

const formFields = computed(() => [
  { name: 'name', label: 'Nom', type: 'text', placeholder: 'Ex: Caisse principale', icon: 'pi pi-wallet' },
  {
    name: 'type',
    label: 'Type',
    type: 'select',
    options: typeOptions,
    optionLabel: 'label',
    optionValue: 'value',
    icon: 'pi pi-building'
  },
  {
    name: 'is_active',
    label: 'Actif',
    type: 'switch',
    description: 'Compte disponible pour les opérations'
  }
])

const saveAccount = async () => {
  try {
    await accountsStore.saveItem(dialog.formData)
    showSuccess('Compte enregistré.')
    dialog.close()
    emit('refresh')
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer le compte.")
  }
}

const accountRowActions = (account) => [
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    command: () => dialog.openEdit(account)
  }
]
</script>
