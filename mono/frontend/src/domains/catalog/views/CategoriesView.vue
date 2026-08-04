<template>
  <section class="dashboard-page">
    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Catégories"
          :count-label="`${categoriesStore.items.length} catégorie(s)`"
          create-label="Nouvelle catégorie"
          :search-term="searchTerm"
          search-placeholder="Rechercher une catégorie..."
          show-search
          @update:search-term="searchTerm = $event"
          @create="dialog.openCreate()"
        />
      </template>
      <template #content>
        <AppTableState
          :loading="categoriesStore.loading || productsStore.loading"
          :is-empty="!categoriesStore.loading && categoryTree.length === 0"
          empty-title="Aucune catégorie"
          empty-text="Créez une catégorie pour structurer votre catalogue."
        >
          <TreeTable
            v-model:expandedKeys="expandedKeys"
            :value="categoryTree"
            data-key="key"
            striped-rows
            :responsive-layout="tableLayout"
          >
            <Column field="name" header="Nom" expander>
              <template #body="{ node }">
                {{ node.data.name }}
              </template>
            </Column>
            <Column v-if="!isMobile" header="Nb produits" style="width: 120px">
              <template #body="{ node }">
                <Tag
                  :value="String(node.data.product_count ?? productCount(node.data.id))"
                  severity="secondary"
                  rounded
                />
              </template>
            </Column>
            <Column v-if="!isMobile" header="Statut" style="width: 120px">
              <template #body="{ node }">
                {{ node.data.status }}
              </template>
            </Column>
            <Column header="Actions" style="width: 90px">
              <template #body="{ node }">
                <AppTableActionsMenu
                  :actions="categoryRowActions(node.data)"
                  aria-label="Actions catégorie"
                />
              </template>
            </Column>
          </TreeTable>
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

    <Dialog
      v-model:visible="productsDialogVisible"
      modal
      :header="productsDialogTitle"
      class="category-products-dialog"
      :style="{ width: 'min(720px, 94vw)' }"
      dismissable-mask
    >
      <AppTableState
        :loading="false"
        :is-empty="categoryProducts.length === 0"
        empty-title="Aucun produit"
        empty-text="Cette catégorie ne contient aucun produit."
      >
        <DataTable :value="categoryProducts" data-key="id" striped-rows responsive-layout="scroll">
          <Column field="name" header="Nom" />
          <Column field="reference" header="Référence" />
          <Column field="status" header="Statut" />
        </DataTable>
      </AppTableState>
      <template #footer>
        <Button
          label="Voir dans Produits"
          icon="pi pi-external-link"
          text
          @click="goToProducts"
        />
        <Button label="Fermer" severity="secondary" @click="productsDialogVisible = false" />
      </template>
    </Dialog>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import TreeTable from 'primevue/treetable'

import AppCrudDialog from '@/domains/shared/components/AppCrudDialog.vue'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useCrudDialog } from '@/domains/shared/composables/useCrudDialog'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useCategoriesStore } from '@/domains/catalog/stores/categories'
import { useProductsStore } from '@/domains/catalog/stores/products'
import { filterCategoryTree } from '@/domains/catalog/utils/buildCategoryTree'

const router = useRouter()
const categoriesStore = useCategoriesStore()
const productsStore = useProductsStore()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { showSuccess, showError, confirmRemoval } = useEntityActions()
const searchTerm = ref('')
const productsDialogVisible = ref(false)
const selectedCategory = ref(null)
const expandedKeys = ref({})

const createEmptyForm = () => ({
  id: null,
  name: '',
  parent_id: null
})

const dialog = useCrudDialog(createEmptyForm)

const parentName = (parentId) => {
  if (!parentId) {
    return ''
  }

  return categoriesStore.items.find((category) => category.id === parentId)?.name || ''
}

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

const productCount = (categoryId) => {
  return productsStore.items.filter((product) => product.category_id === categoryId).length
}

const categoryTree = computed(() =>
  filterCategoryTree(categoriesStore.items, searchTerm.value, (category) => [
    category.name,
    parentName(category.parent_id)
  ])
)

const collectExpandableKeys = (nodes, acc = {}) => {
  for (const node of nodes) {
    if (node.children?.length) {
      acc[node.key] = true
      collectExpandableKeys(node.children, acc)
    }
  }
  return acc
}

watch(
  categoryTree,
  (tree) => {
    expandedKeys.value = collectExpandableKeys(tree)
  },
  { immediate: true }
)

const categoryProducts = computed(() => {
  if (!selectedCategory.value) {
    return []
  }

  return productsStore.items.filter((product) => product.category_id === selectedCategory.value.id)
})

const productsDialogTitle = computed(() => {
  if (!selectedCategory.value) {
    return 'Produits'
  }

  return `Produits — ${selectedCategory.value.name} (${categoryProducts.value.length})`
})

const normalizeForEdit = (category) => ({
  ...createEmptyForm(),
  ...category
})

const openProductsDialog = (category) => {
  selectedCategory.value = category
  productsDialogVisible.value = true
}

const goToProducts = () => {
  productsDialogVisible.value = false
  router.push({
    name: 'catalog-products',
    query: selectedCategory.value?.id ? { category: selectedCategory.value.id } : undefined
  })
}

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

const categoryRowActions = (category) => [
  {
    label: 'Voir les produits',
    icon: 'pi pi-eye',
    command: () => openProductsDialog(category)
  },
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    command: () => dialog.openEdit(normalizeForEdit(category))
  },
  {
    label: 'Supprimer',
    icon: 'pi pi-trash',
    severity: 'danger',
    loading: categoriesStore.isDeleting(category.id),
    command: () => confirmDelete(category)
  }
]

onMounted(async () => {
  try {
    await Promise.all([categoriesStore.fetchAll(), productsStore.fetchAll()])
  } catch (error) {
    showError(error?.message || 'Impossible de charger les catégories.')
  }
})
</script>

