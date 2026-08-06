<template>
  <section class="dashboard-page">
    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Boutiques"
          :count-label="`${items.length} boutique(s)`"
          create-label="Nouvelle boutique"
          :show-create="canCreateShopButton"
          :search-term="searchTerm"
          search-placeholder="Rechercher..."
          show-search
          @update:search-term="searchTerm = $event"
          @create="openCreate"
          :reloading="loading"
          @reload="load"
        />
        <p v-if="canManageShops && !canCreateShop" class="quota-hint">
          Limite du plan atteinte (boutiques).
        </p>
      </template>
      <template #content>
        <AppTableState :loading="loading" :error="error" :is-empty="!loading && filteredItems.length === 0" empty-title="Aucune boutique" @retry="load">
          <DataTable
            :value="filteredItems"
            data-key="id"
            striped-rows
            paginator
            :rows="10"
          >
            <Column field="name" header="Nom" />
            <Column field="slug" header="Slug" />
            <Column header="Statut" style="width: 120px">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'danger'" rounded />
              </template>
            </Column>
            <Column field="users_count" header="Utilisateurs" style="width: 120px" />
            <Column header="Actions" style="width: 120px">
              <template #body="{ data }">
                <AppTableActionsMenu :actions="rowActions(data)" aria-label="Actions boutique" />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <Dialog v-model:visible="dialogVisible" modal :header="dialogMode === 'create' ? 'Nouvelle boutique' : 'Modifier boutique'" :style="{ width: '520px' }">
      <div class="form-grid">
        <div class="field"><label>Nom</label><InputText v-model="form.name" class="w-full" @blur="autoSlug" /></div>
        <div class="field"><label>Slug</label><InputText v-model="form.slug" class="w-full" /></div>
        <div class="field"><label>Téléphone</label><InputText v-model="form.phone" class="w-full" /></div>
        <div class="field"><label>Adresse</label><InputText v-model="form.address" class="w-full" /></div>
        <div v-if="dialogMode === 'edit'" class="field">
          <label>Statut</label>
          <Select v-model="form.status" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Annuler" severity="secondary" text :disabled="submitting" @click="dialogVisible = false" />
        <Button label="Enregistrer" :loading="submitting" :disabled="submitting" @click="save" />
      </template>
    </Dialog>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { usePermissions } from '@/domains/auth/composables/usePermissions'
import { useAuthStore } from '@/domains/auth/stores/auth'
import { shopService } from '@/domains/shop/services/shopService'
import { useAsyncAction } from '@/domains/shared/composables/useAsyncAction'
import { useShopStore } from '@/domains/shop/stores/shop'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const shopStore = useShopStore()
const { hasPermission, canCreateShop } = usePermissions()
const canManageShops = computed(() => hasPermission('platform.shops.manage'))
const canCreateShopButton = computed(() => canManageShops.value && canCreateShop.value)

const items = ref([])
const loading = ref(false)
const error = ref(null)
const searchTerm = ref('')
const dialogVisible = ref(false)
const dialogMode = ref('create')
const editingId = ref(null)

const form = reactive({
  name: '',
  slug: '',
  phone: '',
  address: '',
  status: 'active'
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const filteredItems = computed(() => {
  const term = searchTerm.value.trim().toLowerCase()
  if (!term) {
    return items.value
  }

  return items.value.filter((item) =>
    item.name.toLowerCase().includes(term) || item.slug.toLowerCase().includes(term)
  )
})

const slugify = (value) =>
  value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')

const autoSlug = () => {
  if (dialogMode.value === 'create' && !form.slug) {
    form.slug = slugify(form.name)
  }
}

const load = async () => {
  loading.value = true
  error.value = null
  try {
    items.value = await shopService.fetchShops()
  } catch (err) {
    error.value = err?.message || 'Impossible de charger les boutiques.'
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.name = ''
  form.slug = ''
  form.phone = ''
  form.address = ''
  form.status = 'active'
  editingId.value = null
}

const openCreate = () => {
  if (!canCreateShop.value) {
    toast.add({
      severity: 'warn',
      summary: 'Limite du plan atteinte',
      detail: 'Vous ne pouvez plus créer de boutique avec votre plan actuel.',
      life: 4000
    })
    return
  }

  dialogMode.value = 'create'
  resetForm()
  dialogVisible.value = true
}

const openEdit = (shop) => {
  dialogMode.value = 'edit'
  editingId.value = shop.id
  form.name = shop.name
  form.slug = shop.slug
  form.phone = shop.phone || ''
  form.address = shop.address || ''
  form.status = shop.status
  dialogVisible.value = true
}

const { pending: submitting, run: save } = useAsyncAction(async () => {
  try {
    if (dialogMode.value === 'create') {
      if (!canCreateShop.value) {
        toast.add({
          severity: 'warn',
          summary: 'Limite du plan atteinte',
          detail: 'Vous ne pouvez plus créer de boutique avec votre plan actuel.',
          life: 4000
        })
        return
      }

      await shopService.createShop({ ...form })
      toast.add({ severity: 'success', summary: 'Boutique créée', life: 3000 })
      await authStore.fetchCurrentUser()
    } else {
      await shopService.updateShop(editingId.value, { ...form })
      toast.add({ severity: 'success', summary: 'Boutique mise à jour', life: 3000 })
    }

    dialogVisible.value = false
    await load()
    await shopStore.refreshAccessibleShops()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.response?.data?.error || error.message,
      life: 5000
    })
  }
})

const remove = async (shop) => {
  try {
    await shopService.deleteShop(shop.id)
    toast.add({ severity: 'success', summary: 'Boutique désactivée', life: 3000 })
    await load()
    await shopStore.refreshAccessibleShops()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.response?.data?.error || error.message,
      life: 5000
    })
  }
}

const rowActions = (shop) => [
  {
    label: 'Utilisateurs',
    icon: 'pi pi-users',
    command: () => router.push({ name: 'admin-shop-users', params: { id: shop.id } }),
    visible: hasPermission('platform.shop_users.manage')
  },
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    command: () => openEdit(shop),
    visible: canManageShops.value
  },
  {
    label: 'Désactiver',
    icon: 'pi pi-trash',
    command: () => remove(shop),
    visible: canManageShops.value
  }
].filter((action) => action.visible !== false)

onMounted(load)
</script>

<style scoped>
.quota-hint {
  margin: 0.5rem 0 0;
  color: var(--layout-text-muted);
  font-size: 0.875rem;
}
</style>
