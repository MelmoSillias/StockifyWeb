<template>
  <section class="dashboard-page">
    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          title="Utilisateurs"
          :count-label="`${items.length} utilisateur(s)`"
          create-label="Nouvel utilisateur"
          :search-term="searchTerm"
          search-placeholder="Rechercher..."
          show-search
          @update:search-term="searchTerm = $event"
          @create="openCreate"
          :reloading="loading"
          @reload="load"
        >
          <template #actions>
            <AppTablePrintExportBar table-type="users" :search-term="searchTerm" />
          </template>
        </AppTablePanelHeader>
      </template>
      <template #content>
        <AppTableState :loading="loading" :error="error" :is-empty="!loading && filteredItems.length === 0" empty-title="Aucun utilisateur" @retry="load">
          <DataTable
            :value="filteredItems"
            data-key="id"
            striped-rows
            :responsive-layout="tableLayout"
            paginator
            :rows="10"
          >
            <Column header="Nom">
              <template #body="{ data }">{{ data.first_name }} {{ data.last_name }}</template>
            </Column>
            <Column v-if="!isMobile" field="email" header="Email" />
            <Column v-if="!isMobile" field="username" header="Identifiant" />
            <Column v-if="!isMobile" header="Rôles">
              <template #body="{ data }">{{ (data.roles || []).join(', ') || '—' }}</template>
            </Column>
            <Column header="Statut" style="width: 120px">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'danger'" rounded />
              </template>
            </Column>
            <Column header="Actions" style="width: 90px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="userRowActions(data)"
                  aria-label="Actions utilisateur"
                />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <Dialog v-model:visible="dialogVisible" modal :header="dialogMode === 'create' ? 'Nouvel utilisateur' : 'Modifier utilisateur'" :style="{ width: '520px' }">
      <div class="form-grid">
        <div class="field"><label>Prénom</label><InputText v-model="form.first_name" class="w-full" /></div>
        <div class="field"><label>Nom</label><InputText v-model="form.last_name" class="w-full" /></div>
        <div class="field"><label>Email</label><InputText v-model="form.email" class="w-full" /></div>
        <div class="field"><label>Identifiant</label><InputText v-model="form.username" class="w-full" /></div>
        <div v-if="dialogMode === 'create'" class="field"><label>Mot de passe</label><Password v-model="form.password" class="w-full" toggle-mask :feedback="false" /></div>
        <div class="field">
          <label>Rôles</label>
          <MultiSelect v-model="form.roles" :options="roleOptions" option-label="label" option-value="code" display="chip" class="w-full" />
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
import { useToast } from 'primevue/usetoast'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import MultiSelect from 'primevue/multiselect'
import Password from 'primevue/password'
import Tag from 'primevue/tag'

import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTablePrintExportBar from '@/domains/impression/components/AppTablePrintExportBar.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { usePermissions } from '@/domains/auth/composables/usePermissions'
import { accessService } from '@/domains/access/services/accessService'

const toast = useToast()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { hasPermission } = usePermissions()
const items = ref([])
const roles = ref([])
const loading = ref(false)
const error = ref(null)
const submitting = ref(false)
const searchTerm = ref('')
const dialogVisible = ref(false)
const dialogMode = ref('create')
const editingId = ref(null)

const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  username: '',
  password: '',
  roles: []
})

const roleOptions = computed(() => roles.value)
const filteredItems = computed(() => {
  const term = searchTerm.value.trim().toLowerCase()
  if (!term) return items.value
  return items.value.filter((item) =>
    [item.email, item.username, item.first_name, item.last_name].some((v) => String(v || '').toLowerCase().includes(term))
  )
})

const load = async () => {
  loading.value = true
  error.value = null
  try {
    const [users, roleList] = await Promise.all([accessService.listUsers(), accessService.listRoles()])
    items.value = users
    roles.value = roleList
  } catch (err) {
    error.value = err?.message || 'Impossible de charger les utilisateurs.'
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.first_name = ''
  form.last_name = ''
  form.email = ''
  form.username = ''
  form.password = ''
  form.roles = []
}

const openCreate = () => {
  dialogMode.value = 'create'
  editingId.value = null
  resetForm()
  dialogVisible.value = true
}

const openEdit = (user) => {
  dialogMode.value = 'edit'
  editingId.value = user.id
  form.first_name = user.first_name
  form.last_name = user.last_name
  form.email = user.email
  form.username = user.username
  form.roles = [...(user.roles || [])]
  dialogVisible.value = true
}

const save = async () => {
  submitting.value = true
  try {
    if (dialogMode.value === 'create') {
      await accessService.createUser({ ...form })
      toast.add({ severity: 'success', summary: 'Utilisateur créé', life: 3000 })
    } else {
      await accessService.updateUser(editingId.value, {
        first_name: form.first_name,
        last_name: form.last_name,
        email: form.email,
        username: form.username,
        roles: form.roles
      })
      toast.add({ severity: 'success', summary: 'Utilisateur mis à jour', life: 3000 })
    }
    dialogVisible.value = false
    await load()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: error.response?.data?.error || error.message, life: 4000 })
  } finally {
    submitting.value = false
  }
}

const suspend = async (user) => {
  try {
    await accessService.suspendUser(user.id)
    toast.add({ severity: 'success', summary: 'Utilisateur suspendu', life: 3000 })
    await load()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: error.response?.data?.error || error.message, life: 4000 })
  }
}

const userRowActions = (user) => [
  {
    label: 'Modifier',
    icon: 'pi pi-pencil',
    visible: hasPermission('access.users.update'),
    command: () => openEdit(user)
  },
  {
    label: 'Suspendre',
    icon: 'pi pi-ban',
    severity: 'warn',
    visible: hasPermission('access.users.suspend'),
    disabled: user.status === 'suspended',
    command: () => suspend(user)
  }
]

onMounted(load)
</script>

<style scoped>
.form-grid {
  display: grid;
  gap: 1rem;
}
.field label {
  display: block;
  margin-bottom: 0.35rem;
  font-weight: 600;
}
</style>
