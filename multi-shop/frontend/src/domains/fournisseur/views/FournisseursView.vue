<template>
  <section class="dashboard-page">
    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Fournisseurs"
          :count-label="`${filteredItems.length} fournisseur(s)`"
          create-label="Nouveau fournisseur"
          :show-create="hasPermission('fournisseur.manage')"
          :search-term="searchTerm"
          search-placeholder="Rechercher un fournisseur..."
          show-search
          @update:search-term="searchTerm = $event"
          @create="dialog.openCreate()"
          :reloading="fournisseursStore.loading"
          @reload="load"
        />
      </template>
      <template #content>
        <AppTableState
          :loading="fournisseursStore.loading"
          :error="fournisseursStore.error"
          :is-empty="!fournisseursStore.loading && filteredItems.length === 0"
          empty-title="Aucun fournisseur"
          empty-text="Créez un fournisseur pour gérer vos achats et dettes."
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
            <Column header="Actions" style="width: 160px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="fournisseurRowActions(data)"
                  aria-label="Actions fournisseur"
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
      :title="dialog.mode === 'create' ? 'Nouveau fournisseur' : 'Modifier fournisseur'"
      subtitle="Coordonnées du fournisseur."
      :fields="formFields"
      :loading="fournisseursStore.submitting"
      :general-error="fournisseursStore.error"
      @update:visible="dialog.visible = $event"
      @update:model-value="dialog.formData = $event"
      @submit="saveFournisseur"
    />

    <CreateCommandeFournisseurDialog
      v-model:visible="createCommandeVisible"
      :fournisseur-id="commandeFournisseur?.id"
      :fournisseur-name="commandeFournisseur?.name"
      :loading="creatingCommande"
      @submit="onCreateCommande"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'

import CreateCommandeFournisseurDialog from '@/domains/fournisseur/components/CreateCommandeFournisseurDialog.vue'
import { achatsService } from '@/domains/fournisseur/services/achatsService'
import { fournisseursService } from '@/domains/fournisseur/services/fournisseursService'
import { useFournisseursStore } from '@/domains/fournisseur/stores/fournisseurs'
import { usePermissions } from '@/domains/auth/composables/usePermissions'
import AppCrudDialog from '@/domains/shared/components/AppCrudDialog.vue'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const router = useRouter()
const fournisseursStore = useFournisseursStore()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { hasPermission } = usePermissions()
const { showSuccess, showError, confirmRemoval } = useEntityActions()
const searchTerm = ref('')
const createCommandeVisible = ref(false)
const commandeFournisseur = ref(null)
const creatingCommande = ref(false)

const createEmptyForm = () => ({
  id: null,
  name: '',
  phone: '',
  email: '',
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
  { name: 'name', label: 'Nom', type: 'text', placeholder: 'Ex: Grossiste ABC', icon: 'pi pi-truck' },
  { name: 'phone', label: 'Téléphone', type: 'text', placeholder: 'Ex: 06 12 34 56 78', icon: 'pi pi-phone' },
  { name: 'email', label: 'Email', type: 'text', placeholder: 'Ex: contact@fournisseur.com', icon: 'pi pi-envelope' },
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
    return fournisseursStore.items
  }

  return fournisseursStore.items.filter((item) =>
    [item.name, item.phone, item.email, item.status]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(term))
  )
})

const normalizeForEdit = (fournisseur) => ({
  ...createEmptyForm(),
  ...fournisseur
})

const openJournal = (fournisseur) => {
  router.push({ name: 'fournisseur-journal', params: { id: fournisseur.id } })
}

const openCreateCommande = (fournisseur) => {
  commandeFournisseur.value = fournisseur
  createCommandeVisible.value = true
}

const onCreateCommande = async (payload) => {
  creatingCommande.value = true
  try {
    const commande = await achatsService.create(payload)
    createCommandeVisible.value = false
    showSuccess('Commande achat créée.')
    router.push({
      name: 'fournisseur-journal',
      params: { id: payload.fournisseur_id },
      query: { tab: 'commandes', highlight: commande.id }
    })
  } catch (error) {
    showError(error?.message || 'Impossible de créer la commande.')
  } finally {
    creatingCommande.value = false
  }
}

const saveFournisseur = async () => {
  try {
    await fournisseursStore.saveItem(dialog.formData)
    showSuccess('Fournisseur enregistré.')
    dialog.close()
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer le fournisseur.")
  }
}

const fournisseurRowActions = (fournisseur) => [
  {
    label: 'Nouvelle commande achat',
    icon: 'pi pi-shopping-cart',
    severity: 'success',
    command: () => openCreateCommande(fournisseur)
  },
  {
    label: 'Journal',
    icon: 'pi pi-book',
    command: () => openJournal(fournisseur)
  },
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    visible: hasPermission('fournisseur.manage'),
    command: () => dialog.openEdit(normalizeForEdit(fournisseur))
  },
  {
    label: 'Supprimer',
    icon: 'pi pi-trash',
    severity: 'danger',
    visible: hasPermission('fournisseur.manage'),
    loading: fournisseursStore.isDeleting(fournisseur.id),
    command: () => confirmDelete(fournisseur)
  }
]

const confirmDelete = (fournisseur) => {
  confirmRemoval({
    header: 'Supprimer ce fournisseur ?',
    message: `Le fournisseur ${fournisseur.name} sera supprimé ou archivé s'il possède un historique.`,
    onAccept: async () => {
      try {
        const result = await fournisseursService.remove(fournisseur.id)
        fournisseursStore.removeLocalItem(fournisseur.id)
        showSuccess(
          result.mode === 'soft'
            ? 'Fournisseur archivé (historique conservé).'
            : 'Fournisseur supprimé.'
        )
      } catch (error) {
        showError(error?.message || 'Impossible de supprimer le fournisseur.')
      }
    }
  })
}

const load = async () => {
  try {
    await fournisseursStore.fetchAll()
  } catch (error) {
    showError(error?.message || 'Impossible de charger les fournisseurs.')
  }
}

onMounted(load)
</script>

