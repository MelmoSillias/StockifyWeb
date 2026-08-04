<template>
  <section class="dashboard-page">
    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Rôles"
          :count-label="`${items.length} rôle(s)`"
          create-label="Nouveau rôle"
          :search-term="searchTerm"
          search-placeholder="Rechercher..."
          show-search
          :reloading="loading"
          @update:search-term="searchTerm = $event"
          @create="openCreate"
          @reload="load"
        />
      </template>
      <template #content>
        <AppTableState
          :loading="loading"
          :error="error"
          :is-empty="!loading && filteredItems.length === 0"
          empty-title="Aucun rôle"
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
            <Column field="label" header="Libellé" sortable />
            <Column v-if="!isMobile" field="code" header="Code" />
            <Column v-if="!isMobile" header="Permissions" style="min-width: 220px">
              <template #body="{ data }">{{ data.permissions?.length || 0 }} permission(s)</template>
            </Column>
            <Column header="Type" style="width: 120px">
              <template #body="{ data }">
                <Tag :value="data.is_system ? 'Système' : 'Personnalisé'" :severity="data.is_system ? 'info' : 'secondary'" rounded />
              </template>
            </Column>
            <Column header="Actions" style="width: 90px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="roleRowActions(data)"
                  aria-label="Actions rôle"
                />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <Dialog v-model:visible="dialogVisible" modal :header="dialogMode === 'create' ? 'Nouveau rôle' : 'Modifier rôle'" :style="{ width: '720px' }">
      <div class="form-grid">
        <div v-if="dialogMode === 'create'" class="field"><label>Code</label><InputText v-model="form.code" class="w-full" /></div>
        <div class="field"><label>Libellé</label><InputText v-model="form.label" class="w-full" /></div>
        <div class="field"><label>Description</label><Textarea v-model="form.description" rows="2" class="w-full" auto-resize /></div>
        <div class="field">
          <label>Permissions</label>
          <div class="permissions-grid">
            <label v-for="permission in permissions" :key="permission.code" class="permission-item">
              <Checkbox v-model="form.permissions" :input-id="permission.code" :value="permission.code" />
              <span>{{ permission.label }}</span>
            </label>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Annuler" severity="secondary" text @click="dialogVisible = false" />
        <Button label="Enregistrer" :loading="submitting" @click="save" />
      </template>
    </Dialog>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Checkbox from 'primevue/checkbox'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'

import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { usePermissions } from '@/domains/auth/composables/usePermissions'
import { accessService } from '@/domains/access/services/accessService'

const toast = useToast()
const confirm = useConfirm()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { hasPermission } = usePermissions()
const items = ref([])
const permissions = ref([])
const loading = ref(false)
const error = ref(null)
const submitting = ref(false)
const searchTerm = ref('')
const dialogVisible = ref(false)
const dialogMode = ref('create')
const editingId = ref(null)

const form = reactive({
  code: '',
  label: '',
  description: '',
  permissions: []
})

const filteredItems = computed(() => {
  const term = searchTerm.value.trim().toLowerCase()
  if (!term) return items.value
  return items.value.filter((item) => [item.code, item.label].some((v) => String(v || '').toLowerCase().includes(term)))
})

const load = async () => {
  loading.value = true
  error.value = null
  try {
    const [roleList, permissionList] = await Promise.all([accessService.listRoles(), accessService.listPermissions()])
    items.value = roleList
    permissions.value = permissionList
  } catch (err) {
    error.value = err?.message || 'Impossible de charger les rôles.'
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.code = ''
  form.label = ''
  form.description = ''
  form.permissions = []
}

const openCreate = () => {
  dialogMode.value = 'create'
  editingId.value = null
  resetForm()
  dialogVisible.value = true
}

const openEdit = (role) => {
  dialogMode.value = 'edit'
  editingId.value = role.id
  form.code = role.code
  form.label = role.label
  form.description = role.description || ''
  form.permissions = [...(role.permissions || [])]
  dialogVisible.value = true
}

const save = async () => {
  submitting.value = true
  try {
    if (dialogMode.value === 'create') {
      await accessService.createRole({ ...form })
      toast.add({ severity: 'success', summary: 'Rôle créé', life: 3000 })
    } else {
      await accessService.updateRole(editingId.value, {
        label: form.label,
        description: form.description,
        permissions: form.permissions
      })
      toast.add({ severity: 'success', summary: 'Rôle mis à jour', life: 3000 })
    }
    dialogVisible.value = false
    await load()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: error.response?.data?.error || error.message, life: 4000 })
  } finally {
    submitting.value = false
  }
}

const confirmDelete = (role) => {
  confirm.require({
    header: 'Supprimer ce rôle ?',
    message: `Supprimer le rôle ${role.label} ?`,
    acceptLabel: 'Supprimer',
    rejectLabel: 'Annuler',
    accept: async () => {
      try {
        await accessService.deleteRole(role.id)
        toast.add({ severity: 'success', summary: 'Rôle supprimé', life: 3000 })
        await load()
      } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: error.response?.data?.error || error.message, life: 4000 })
      }
    }
  })
}

const roleRowActions = (role) => [
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    visible: hasPermission('access.roles.manage'),
    command: () => openEdit(role)
  },
  {
    label: 'Supprimer',
    icon: 'pi pi-trash',
    severity: 'danger',
    visible: hasPermission('access.roles.manage') && !role.is_system,
    command: () => confirmDelete(role)
  }
]

onMounted(load)
</script>

<style scoped>
.form-grid { display: grid; gap: 1rem; }
.field label { display: block; margin-bottom: 0.35rem; font-weight: 600; }
.permissions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 0.5rem;
  max-height: 320px;
  overflow: auto;
  padding: 0.5rem;
  border: 1px solid var(--p-content-border-color);
  border-radius: 8px;
}
.permission-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
</style>
