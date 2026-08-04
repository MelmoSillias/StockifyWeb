<template>
  <Card class="dashboard-panel">
    <template #title>
      <AppTablePanelHeader
        title="Modes de paiement"
        :count-label="`${paymentMethodsStore.items.length} mode(s)`"
        create-label="Nouveau mode"
        show-create
        @create="dialog.openCreate()"
      />
    </template>
    <template #content>
      <AppTableState
        :loading="paymentMethodsStore.loading"
        :is-empty="!paymentMethodsStore.loading && paymentMethodsStore.items.length === 0"
        empty-title="Aucun mode de paiement"
        empty-text="Configurez les modes disponibles pour les encaissements."
      >
        <DataTable
          :value="paymentMethodsStore.items"
          data-key="id"
          striped-rows
          :responsive-layout="tableLayout"
          paginator
          :rows="10"
        >
          <Column v-if="!isMobile" field="code" header="Code" style="width: 140px" />
          <Column field="label" header="Libellé" sortable />
          <Column v-if="!isMobile" header="Compte cible">
            <template #body="{ data }">{{ accountLabel(data.compte_id) }}</template>
          </Column>
          <Column v-if="!isMobile" header="Transaction" style="width: 120px">
            <template #body="{ data }">
              <Tag
                :value="data.generates_transaction ? 'Oui' : 'Non'"
                :severity="data.generates_transaction ? 'info' : 'secondary'"
                rounded
              />
            </template>
          </Column>
          <Column header="Statut" style="width: 110px">
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
                :actions="paymentMethodRowActions(data)"
                aria-label="Actions mode de paiement"
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
    :title="dialog.mode === 'create' ? 'Nouveau mode de paiement' : 'Modifier mode de paiement'"
    subtitle="Associez chaque mode à un compte de trésorerie."
    :fields="formFields"
    :loading="paymentMethodsStore.submitting"
    :general-error="paymentMethodsStore.error"
    @update:visible="dialog.visible = $event"
    @update:model-value="dialog.formData = $event"
    @submit="savePaymentMethod"
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
import { usePaymentMethodsStore } from '@/domains/finance/stores/paymentMethods'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const props = defineProps({
  accounts: { type: Array, default: () => [] }
})

const paymentMethodsStore = usePaymentMethodsStore()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { showSuccess, showError, confirmRemoval } = useEntityActions()

const createEmptyForm = () => ({
  id: null,
  code: '',
  label: '',
  compte_id: null,
  is_active: true,
  generates_transaction: true
})

const dialog = useCrudDialog(createEmptyForm)

const accountOptions = computed(() =>
  props.accounts
    .filter((account) => account.is_active)
    .map((account) => ({
      label: `${account.name} (${accountTypeLabel(account.type)})`,
      value: account.id
    }))
)

const formFields = computed(() => [
  {
    name: 'code',
    label: 'Code',
    type: 'text',
    placeholder: 'Ex: cash',
    icon: 'pi pi-hashtag'
  },
  { name: 'label', label: 'Libellé', type: 'text', placeholder: 'Ex: Espèces', icon: 'pi pi-tag' },
  {
    name: 'compte_id',
    label: 'Compte cible',
    type: 'select',
    options: accountOptions.value,
    optionLabel: 'label',
    optionValue: 'value',
    icon: 'pi pi-wallet'
  },
  { name: 'generates_transaction', label: 'Génère une transaction', type: 'switch', description: 'Crée une entrée de trésorerie' },
  { name: 'is_active', label: 'Actif', type: 'switch', description: 'Disponible dans les formulaires de vente' }
])

const accountLabel = (compteId) => {
  const account = props.accounts.find((entry) => entry.id === compteId)
  if (!account) {
    return '—'
  }

  return `${account.name} (${accountTypeLabel(account.type)})`
}

const savePaymentMethod = async () => {
  try {
    await paymentMethodsStore.saveItem(dialog.formData)
    showSuccess('Mode de paiement enregistré.')
    dialog.close()
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer le mode de paiement.")
  }
}

const confirmDelete = (mode) => {
  confirmRemoval({
    header: 'Supprimer ce mode ?',
    message: `Le mode « ${mode.label} » sera supprimé s'il n'est pas utilisé.`,
    onAccept: async () => {
      try {
        await paymentMethodsStore.removeItem(mode.id)
        showSuccess('Mode de paiement supprimé.')
      } catch (error) {
        showError(error?.message || 'Impossible de supprimer le mode de paiement.')
      }
    }
  })
}

const paymentMethodRowActions = (mode) => [
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    command: () => dialog.openEdit(mode)
  },
  {
    label: 'Supprimer',
    icon: 'pi pi-trash',
    severity: 'danger',
    loading: paymentMethodsStore.isDeleting(mode.id),
    command: () => confirmDelete(mode)
  }
]
</script>

