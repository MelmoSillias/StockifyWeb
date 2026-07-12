<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Catalogue</p>
        <h1 class="dashboard-title">Catégories</h1>
        <p class="dashboard-description">Structurez votre catalogue par familles de produits.</p>
      </div>
    </div>

    <AppEntityToolbar
      :search-term="searchTerm"
      search-placeholder="Rechercher une catégorie..."
      create-label="Nouvelle catégorie"
      :count-label="`${filteredCategories.length} catégorie(s)`"
      @update:search-term="searchTerm = $event"
      @create="dialog.openCreate()"
    />

    <Card class="dashboard-panel">
      <template #content>
        <AppTableState
          :loading="categoriesStore.loading"
          :is-empty="!categoriesStore.loading && filteredCategories.length === 0"
          empty-title="Aucune catégorie"
          empty-text="Créez une catégorie pour structurer votre catalogue."
        >
          <DataTable :value="filteredCategories" data-key="id" striped-rows responsive-layout="scroll">
            <Column field="name" header="Nom" />
            <Column header="Parent">
              <template #body="{ data }">
                {{ parentName(data.parent_id) }}
              </template>
            </Column>
            <Column field="status" header="Statut" />
            <Column header="Actions" style="width: 170px">
              <template #body="{ data }">
                <div class="actions-cell">
                  <Button icon="pi pi-pencil" text rounded @click="dialog.openEdit(normalizeForEdit(data))" />
                  <Button
                    icon="pi pi-trash"
                    text
                    rounded
                    severity="danger"
                    :loading="categoriesStore.isDeleting(data.id)"
                    @click="confirmDelete(data)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <AppCrudDialog
      :visible="dialog.visible"
      :model-value="dialog.formData"
      :title="dialog.mode === 'create' ? 'Nouvelle catégorie' : 'Modifier catégorie'"
      subtitle="Nom et catégorie parente, le cas échéant."
      :fields="formFields"
      :loading="categoriesStore.submitting"
      :general-error="categoriesStore.error"
      @update:visible="dialog.visible = $event"
      @update:model-value="dialog.formData = $event"
      @submit="saveCategory"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'

import AppCrudDialog from '@/domains/shared/components/AppCrudDialog.vue'
import AppEntityToolbar from '@/domains/shared/components/AppEntityToolbar.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useCategoriesStore } from '@/domains/catalog/stores/categories'

const categoriesStore = useCategoriesStore()
const { showSuccess, showError, confirmRemoval } = useEntityActions()
const searchTerm = ref('')

const createEmptyForm = () => ({
  id: null,
  name: '',
  parent_id: null
})

const dialog = useCrudDialog(createEmptyForm)

const parentOptions = computed(() => [
  { label: 'Aucune (racine)', value: null },
  ...categoriesStore.items.map((category) => ({
    label: category.name,
    value: category.id
  }))
])

const formFields = computed(() => [
  { name: 'name', label: 'Nom', type: 'text', placeholder: 'Ex: Boissons', icon: 'pi pi-tag' },
  {
    name: 'parent_id',
    label: 'Catégorie parente',
    type: 'select',
    options: parentOptions.value,
    optionLabel: 'label',
    optionValue: 'value',
    placeholder: 'Sélectionner',
    icon: 'pi pi-sitemap'
  }
])

const filteredCategories = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()
  if (!query) {
    return categoriesStore.items
  }

  return categoriesStore.items.filter((category) => {
    const parent = parentName(category.parent_id)
    return [category.name, parent].some((value) => String(value || '').toLowerCase().includes(query))
  })
})

const parentName = (parentId) => {
  if (!parentId) {
    return '—'
  }

  return categoriesStore.items.find((category) => category.id === parentId)?.name || parentId
}

const normalizeForEdit = (category) => ({
  ...createEmptyForm(),
  ...category
})

const saveCategory = async () => {
  try {
    await categoriesStore.saveItem(dialog.formData)
    showSuccess('Catégorie enregistrée.')
    dialog.close()
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer la catégorie.")
  }
}

const confirmDelete = (category) => {
  confirmRemoval({
    header: 'Supprimer cette catégorie ?',
    message: `La catégorie ${category.name} sera supprimée définitivement.`,
    onAccept: async () => {
      await categoriesStore.removeItem(category.id)
      showSuccess('Catégorie supprimée.')
    }
  })
}

onMounted(async () => {
  try {
    await categoriesStore.fetchAll()
  } catch (error) {
    showError(error?.message || 'Impossible de charger les catégories.')
  }
})
</script>

<style scoped>
.actions-cell {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
</style>
