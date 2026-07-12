<template>
  <section class="dashboard-page">
    <div class="dashboard-hero dashboard-hero--compact">
      <div>
        <p class="dashboard-eyebrow">Catalogue</p>
        <h1 class="dashboard-title">Produits</h1>
        <p class="dashboard-description">Produits de la boutique sélectionnée.</p>
      </div>
    </div>

    <AppEntityToolbar
      :search-term="searchTerm"
      search-placeholder="Rechercher un produit..."
      create-label="Nouveau produit"
      :count-label="`${filteredProducts.length} produit(s)`"
      @update:search-term="searchTerm = $event"
      @create="dialog.openCreate()"
    />

    <Card class="dashboard-panel">
      <template #content>
        <AppTableState
          :loading="productsStore.loading"
          :is-empty="!productsStore.loading && filteredProducts.length === 0"
          empty-title="Aucun produit"
          empty-text="Créez un produit pour alimenter votre catalogue."
        >
          <DataTable :value="filteredProducts" data-key="id" striped-rows responsive-layout="scroll">
            <Column field="name" header="Nom" />
            <Column field="reference" header="Référence" />
            <Column header="Catégorie">
              <template #body="{ data }">
                {{ categoryName(data.category_id) }}
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
                    :loading="productsStore.isDeleting(data.id)"
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
      :title="dialog.mode === 'create' ? 'Nouveau produit' : 'Modifier produit'"
      subtitle="Nom, référence et catégorie du produit."
      :fields="formFields"
      :loading="productsStore.submitting"
      :general-error="productsStore.error"
      @update:visible="dialog.visible = $event"
      @update:model-value="dialog.formData = $event"
      @submit="saveProduct"
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
import { useProductsStore } from '@/domains/catalog/stores/products'

const productsStore = useProductsStore()
const categoriesStore = useCategoriesStore()
const { showSuccess, showError, confirmRemoval } = useEntityActions()
const searchTerm = ref('')

const createEmptyForm = () => ({
  id: null,
  name: '',
  reference: '',
  description: '',
  category_id: null
})

const dialog = useCrudDialog(createEmptyForm)

const categoryOptions = computed(() => [
  { label: 'Aucune', value: null },
  ...categoriesStore.items.map((category) => ({
    label: category.name,
    value: category.id
  }))
])

const formFields = computed(() => [
  { name: 'name', label: 'Nom', type: 'text', placeholder: 'Ex: Café arabica', icon: 'pi pi-tag' },
  { name: 'reference', label: 'Référence', type: 'text', placeholder: 'Ex: PRD-001', icon: 'pi pi-barcode' },
  { name: 'description', label: 'Description', type: 'textarea', placeholder: 'Description du produit', icon: 'pi pi-align-left' },
  {
    name: 'category_id',
    label: 'Catégorie',
    type: 'select',
    options: categoryOptions.value,
    optionLabel: 'label',
    optionValue: 'value',
    placeholder: 'Sélectionner',
    icon: 'pi pi-sitemap'
  }
])

const filteredProducts = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()
  if (!query) {
    return productsStore.items
  }

  return productsStore.items.filter((product) => {
    const category = categoryName(product.category_id)
    return [product.name, product.reference, category].some((value) => String(value || '').toLowerCase().includes(query))
  })
})

const categoryName = (categoryId) => {
  if (!categoryId) {
    return '—'
  }

  return categoriesStore.items.find((category) => category.id === categoryId)?.name || categoryId
}

const normalizeForEdit = (product) => ({
  ...createEmptyForm(),
  ...product
})

const saveProduct = async () => {
  try {
    await productsStore.saveItem(dialog.formData)
    showSuccess('Produit enregistré.')
    dialog.close()
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer le produit.")
  }
}

const confirmDelete = (product) => {
  confirmRemoval({
    header: 'Supprimer ce produit ?',
    message: `Le produit ${product.name} sera supprimé définitivement.`,
    onAccept: async () => {
      await productsStore.removeItem(product.id)
      showSuccess('Produit supprimé.')
    }
  })
}

onMounted(async () => {
  try {
    await Promise.all([categoriesStore.fetchAll(), productsStore.fetchAll()])
  } catch (error) {
    showError(error?.message || 'Impossible de charger les produits.')
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
