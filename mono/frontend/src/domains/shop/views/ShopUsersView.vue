<template>
  <section class="dashboard-page">
    <Card class="dashboard-panel">
      <template #title>
        <AppTablePanelHeader
          :title="`Utilisateurs — ${shopName}`"
          :count-label="`${items.length} utilisateur(s)`"
          create-label="Nouvel utilisateur"
          :search-term="searchTerm"
          search-placeholder="Rechercher..."
          show-search
          @update:search-term="searchTerm = $event"
          @create="openCreate"
          :reloading="loading"
          @reload="load"
        />
      </template>
      <template #content>
        <AppTableState :loading="loading" :error="error" :is-empty="!loading && filteredItems.length === 0" empty-title="Aucun utilisateur" @retry="load">
          <DataTable :value="filteredItems" data-key="id" striped-rows paginator :rows="10">
            <Column header="Nom">
              <template #body="{ data }">{{ data.first_name }} {{ data.last_name }}</template>
            </Column>
            <Column field="username" header="Identifiant" />
            <Column field="email" header="Email" />
            <Column header="Rôles">
              <template #body="{ data }">{{ (data.roles || []).join(', ') || '—' }}</template>
            </Column>
            <Column header="Statut" style="width: 120px">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'danger'" rounded />
              </template>
            </Column>
          </DataTable>
        </AppTableState>
      </template>
    </Card>

    <Dialog v-model:visible="dialogVisible" modal header="Nouvel utilisateur boutique" :style="{ width: '520px' }">
      <div class="form-grid">
        <div class="field"><label>Identifiant</label><InputText v-model="form.username" class="w-full" /></div>
        <div class="field"><label>Prénom</label><InputText v-model="form.first_name" class="w-full" /></div>
        <div class="field"><label>Nom</label><InputText v-model="form.last_name" class="w-full" /></div>
        <div class="field">
          <label>Rôles</label>
          <MultiSelect v-model="form.roles" :options="roleOptions" option-label="label" option-value="code" display="chip" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Annuler" severity="secondary" text :disabled="submitting" @click="dialogVisible = false" />
        <Button label="Créer" :loading="submitting" :disabled="submitting" @click="save" />
      </template>
    </Dialog>

    <Dialog v-model:visible="passwordDialogVisible" modal header="Mot de passe généré" :closable="false" :style="{ width: '480px' }">
      <p>Conservez ce mot de passe : il ne sera plus affiché.</p>
      <div class="generated-password">{{ generatedPassword }}</div>
      <template #footer>
        <Button label="Copier" icon="pi pi-copy" @click="copyPassword" />
        <Button label="Fermer" @click="passwordDialogVisible = false" />
      </template>
    </Dialog>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import MultiSelect from 'primevue/multiselect'
import Tag from 'primevue/tag'

import AppTablePanelHeader from '@/domains/shared/components/AppTablePanelHeader.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { accessService } from '@/domains/access/services/accessService'
import { shopService } from '@/domains/shop/services/shopService'
import { useAsyncAction } from '@/domains/shared/composables/useAsyncAction'
import { useShopStore } from '@/domains/shop/stores/shop'

const route = useRoute()
const toast = useToast()
const shopStore = useShopStore()

const shopId = computed(() => route.params.id)
const shopName = computed(() => shopStore.accessibleShops.find((shop) => shop.id === shopId.value)?.name || 'Boutique')

const items = ref([])
const loading = ref(false)
const error = ref(null)
const searchTerm = ref('')
const dialogVisible = ref(false)
const passwordDialogVisible = ref(false)
const generatedPassword = ref('')
const roleOptions = ref([])

const form = reactive({
  username: '',
  first_name: '',
  last_name: '',
  roles: []
})

const filteredItems = computed(() => {
  const term = searchTerm.value.trim().toLowerCase()
  if (!term) {
    return items.value
  }

  return items.value.filter((item) =>
    `${item.first_name} ${item.last_name}`.toLowerCase().includes(term)
    || item.username.toLowerCase().includes(term)
    || item.email.toLowerCase().includes(term)
  )
})

const load = async () => {
  loading.value = true
  error.value = null
  try {
    items.value = await shopService.fetchShopUsers(shopId.value)
  } catch (err) {
    error.value = err?.message || 'Impossible de charger les utilisateurs.'
  } finally {
    loading.value = false
  }
}

const loadRoles = async () => {
  const roles = await accessService.listRoles()
  roleOptions.value = roles.map((role) => ({ code: role.code, label: role.label }))
}

const openCreate = () => {
  form.username = ''
  form.first_name = ''
  form.last_name = ''
  form.roles = ['gerant']
  dialogVisible.value = true
}

const { pending: submitting, run: save } = useAsyncAction(async () => {
  try {
    const result = await shopService.createShopUser(shopId.value, { ...form })
    generatedPassword.value = result.generated_password
    dialogVisible.value = false
    passwordDialogVisible.value = true
    await load()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: error.response?.data?.error || error.message,
      life: 5000
    })
  }
})

const copyPassword = async () => {
  await navigator.clipboard.writeText(generatedPassword.value)
  toast.add({ severity: 'info', summary: 'Mot de passe copié', life: 2000 })
}

onMounted(async () => {
  await Promise.all([load(), loadRoles()])
})
</script>

<style scoped>
.generated-password {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  background: var(--surface-100);
  font-family: monospace;
  font-size: 1.1rem;
}
</style>
