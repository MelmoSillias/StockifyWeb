<template>
  <section class="dashboard-page fournisseur-journal">
    <div class="fournisseur-journal__header">
      <Button
        label="Fournisseurs"
        icon="pi pi-arrow-left"
        text
        @click="router.push({ name: 'fournisseurs' })"
      />
      <div v-if="fournisseur" class="fournisseur-journal__title-block">
        <h1 class="fournisseur-journal__title">{{ fournisseur.name }}</h1>
        <div class="fournisseur-journal__meta">
          <Tag :value="statusLabel(fournisseur.status)" :severity="statusSeverity(fournisseur.status)" rounded />
          <Tag v-if="fournisseur.is_deleted" value="Archivé" severity="secondary" rounded />
        </div>
      </div>
    </div>

    <Card class="dashboard-panel">
      <template #title>
        <div class="fournisseur-journal__actions">
          <Button
            icon="pi pi-refresh"
            text
            rounded
            severity="secondary"
            :loading="fournisseursStore.detailLoading"
            aria-label="Actualiser"
            v-tooltip.top="'Actualiser'"
            @click="load()"
          />
        </div>
      </template>
      <template #content>
        <AppTableState
          :loading="fournisseursStore.detailLoading && !fournisseur"
          :error="loadError"
          :is-empty="!fournisseursStore.detailLoading && !fournisseur && Boolean(loadError)"
          empty-title="Fournisseur introuvable"
          :empty-text="loadError || 'Ce fournisseur n\'existe pas ou a été supprimé.'"
          @retry="load()"
        >
          <Tabs v-if="fournisseur" v-model:value="activeTab" @update:value="onTabChange">
            <TabList>
              <Tab value="info">Informations</Tab>
              <Tab value="commandes">Commandes</Tab>
              <Tab value="dettes">Dettes</Tab>
              <Tab value="paiements">Paiements</Tab>
            </TabList>

            <TabPanels>
              <TabPanel value="info">
                <div class="fournisseur-journal__info-grid">
                  <div>
                    <span class="fournisseur-journal__label">Nom</span>
                    <strong>{{ fournisseur.name }}</strong>
                  </div>
                  <div>
                    <span class="fournisseur-journal__label">Téléphone</span>
                    <span>{{ fournisseur.phone || '—' }}</span>
                  </div>
                  <div>
                    <span class="fournisseur-journal__label">Email</span>
                    <span>{{ fournisseur.email || '—' }}</span>
                  </div>
                  <div>
                    <span class="fournisseur-journal__label">Statut</span>
                    <span>{{ statusLabel(fournisseur.status) }}</span>
                  </div>
                  <div>
                    <span class="fournisseur-journal__label">Créé le</span>
                    <span>{{ formatDateTime(fournisseur.created_at) }}</span>
                  </div>
                  <div>
                    <span class="fournisseur-journal__label">Modifié le</span>
                    <span>{{ formatDateTime(fournisseur.updated_at) }}</span>
                  </div>
                </div>
              </TabPanel>

              <TabPanel value="commandes">
                <div class="fournisseur-journal__tab-toolbar">
                  <Button
                    label="Nouvelle commande achat"
                    icon="pi pi-plus-circle"
                    @click="openCreateCommande"
                  />
                </div>

                <AppTableState
                  :loading="tabLoading.commandes"
                  :is-empty="!tabLoading.commandes && tabData.commandes.length === 0"
                  empty-title="Aucune commande"
                  empty-text="Créez une commande achat pour ce fournisseur."
                >
                  <DataTable
                    :value="tabData.commandes"
                    data-key="id"
                    striped-rows
                    :responsive-layout="tableLayout"
                    paginator
                    :rows="10"
                  >
                    <Column field="reference" header="Référence" />
                    <Column v-if="!isMobile" header="Date">
                      <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
                    </Column>
                    <Column header="Statut">
                      <template #body="{ data }">
                        <Tag
                          :value="orderStatusLabel(data.status)"
                          :severity="orderStatusSeverity(data.status)"
                          rounded
                        />
                      </template>
                    </Column>
                    <Column header="Total">
                      <template #body="{ data }">{{ formatMoney(data.total_amount) }}</template>
                    </Column>
                    <Column header="Actions" style="width: 160px">
                      <template #body="{ data }">
                        <AppTableActionsMenu
                          :actions="commandeRowActions(data)"
                          aria-label="Actions commande"
                        />
                      </template>
                    </Column>
                  </DataTable>
                </AppTableState>
              </TabPanel>

              <TabPanel value="dettes">
                <div class="fournisseur-journal__tab-toolbar">
                  <Button
                    label="Nouvelle dette"
                    icon="pi pi-plus-circle"
                    severity="secondary"
                    @click="createDetteVisible = true"
                  />
                </div>

                <DettesTable
                  :items="tabData.dettes"
                  :loading="tabLoading.dettes"
                  :show-fournisseur-column="false"
                  :payment-loading-id="paymentLoadingId"
                  empty-text="Ce fournisseur n'a pas encore de dette enregistrée."
                  @pay="openDettePayment"
                />
              </TabPanel>

              <TabPanel value="paiements">
                <AppTableState
                  :loading="tabLoading.paiements"
                  :is-empty="!tabLoading.paiements && tabData.paiements.length === 0"
                  empty-title="Aucun paiement"
                  empty-text="Ce fournisseur n'a pas encore de décaissement enregistré."
                >
                  <DataTable
                    :value="tabData.paiements"
                    data-key="id"
                    striped-rows
                    :responsive-layout="tableLayout"
                    paginator
                    :rows="10"
                  >
                    <Column field="reference" header="Référence" />
                    <Column v-if="!isMobile" header="Date">
                      <template #body="{ data }">{{ formatDateTime(data.paid_at) }}</template>
                    </Column>
                    <Column header="Montant">
                      <template #body="{ data }">{{ formatMoney(data.amount) }}</template>
                    </Column>
                    <Column v-if="!isMobile" header="Méthode">
                      <template #body="{ data }">
                        <Tag :value="paymentMethodLabel(data.method)" severity="secondary" rounded />
                      </template>
                    </Column>
                    <Column header="Statut">
                      <template #body="{ data }">
                        <Tag
                          :value="data.is_cancelled ? 'Annulé' : 'Actif'"
                          :severity="data.is_cancelled ? 'danger' : 'success'"
                          rounded
                        />
                      </template>
                    </Column>
                    <Column header="Actions" style="width: 90px">
                      <template #body="{ data }">
                        <AppTableActionsMenu
                          :actions="paiementRowActions(data)"
                          aria-label="Actions paiement"
                        />
                      </template>
                    </Column>
                  </DataTable>
                </AppTableState>
              </TabPanel>
            </TabPanels>
          </Tabs>
        </AppTableState>
      </template>
    </Card>

    <CreateCommandeFournisseurDialog
      v-model:visible="createCommandeVisible"
      :fournisseur-id="fournisseurId"
      :fournisseur-name="fournisseur?.name"
      :loading="creatingCommande"
      @submit="onCreateCommande"
    />

    <ConfirmCommandeFournisseurDialog
      v-model:visible="confirmVisible"
      :reference="selectedCommande?.reference"
      :loading="confirming"
      @confirm="onConfirmCommande"
    />

    <CommandeFournisseurDetailDialog
      v-model:visible="commandeDetailVisible"
      :commande="selectedCommande"
    />

    <RecevoirCommandeDialog
      v-model:visible="recevoirVisible"
      :commande="selectedCommande"
      :loading="receiving"
      @confirm="onRecevoirConfirm"
    />

    <CreateDetteFournisseurDialog
      v-model:visible="createDetteVisible"
      :fournisseur-id="fournisseurId"
      :fournisseur-name="fournisseur?.name"
      :loading="creatingDette"
      @submit="onCreateDette"
    />

    <RecordSupplierPaymentDialog
      v-model:visible="paymentVisible"
      :balance="paymentDette?.balance"
      :issued-at="paymentDette?.issued_at"
      :loading="paying"
      @confirm="onPaymentConfirm"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Tag from 'primevue/tag'

import CommandeFournisseurDetailDialog from '@/domains/fournisseur/components/CommandeFournisseurDetailDialog.vue'
import ConfirmCommandeFournisseurDialog from '@/domains/fournisseur/components/ConfirmCommandeFournisseurDialog.vue'
import CreateCommandeFournisseurDialog from '@/domains/fournisseur/components/CreateCommandeFournisseurDialog.vue'
import CreateDetteFournisseurDialog from '@/domains/fournisseur/components/CreateDetteFournisseurDialog.vue'
import DettesTable from '@/domains/fournisseur/components/DettesTable.vue'
import RecevoirCommandeDialog from '@/domains/fournisseur/components/RecevoirCommandeDialog.vue'
import RecordSupplierPaymentDialog from '@/domains/fournisseur/components/RecordSupplierPaymentDialog.vue'
import { achatsService } from '@/domains/fournisseur/services/achatsService'
import { dettesService } from '@/domains/fournisseur/services/dettesService'
import { fournisseursService } from '@/domains/fournisseur/services/fournisseursService'
import { useFournisseursStore } from '@/domains/fournisseur/stores/fournisseurs'
import { usePaymentMethods } from '@/domains/finance/composables/usePaymentMethods'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { toIsoDateTime } from '@/domains/shared/services/createCrudService'

const route = useRoute()
const router = useRouter()
const fournisseursStore = useFournisseursStore()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { formatDateTime, formatMoney } = useDisplayFormatters()
const { resolveMethodLabel: paymentMethodLabel } = usePaymentMethods()
const { showError, showSuccess, confirmRemoval } = useEntityActions()

const activeTab = ref('info')
const loadError = ref(null)
const createCommandeVisible = ref(false)
const createDetteVisible = ref(false)
const confirmVisible = ref(false)
const commandeDetailVisible = ref(false)
const recevoirVisible = ref(false)
const selectedCommande = ref(null)
const pendingCommandeId = ref(null)
const receivingId = ref(null)
const receiving = ref(false)
const creatingCommande = ref(false)
const creatingDette = ref(false)
const confirming = ref(false)
const pendingPaiementId = ref(null)
const paymentVisible = ref(false)
const paymentDette = ref(null)
const paymentLoadingId = ref(null)
const paying = ref(false)

const tabData = reactive({
  commandes: [],
  dettes: [],
  paiements: []
})

const tabLoading = reactive({
  commandes: false,
  dettes: false,
  paiements: false
})

const loadedTabs = ref(new Set(['info']))

const fournisseurId = computed(() => route.params.id)
const fournisseur = computed(() => fournisseursStore.currentItem)

const statusOptions = [
  { label: 'Actif', value: 'active' },
  { label: 'Suspendu', value: 'suspended' }
]

const statusLabel = (status) => statusOptions.find((option) => option.value === status)?.label || status
const statusSeverity = (status) => (status === 'suspended' ? 'warn' : 'success')

const orderStatusMap = {
  initiee: { label: 'Initiée', severity: 'secondary' },
  confirmee: { label: 'Confirmée', severity: 'info' },
  recue: { label: 'Reçue', severity: 'success' },
  annulee: { label: 'Annulée', severity: 'danger' }
}

const orderStatusLabel = (status) => orderStatusMap[status]?.label || status
const orderStatusSeverity = (status) => orderStatusMap[status]?.severity || 'secondary'
const canCancelCommande = (status) => ['initiee', 'confirmee'].includes(status)

const commandeRowActions = (commande) => [
  {
    label: 'Voir',
    icon: 'pi pi-eye',
    command: () => openCommandeDetail(commande)
  },
  {
    label: 'Confirmer',
    icon: 'pi pi-check',
    severity: 'success',
    visible: commande.status === 'initiee',
    loading: pendingCommandeId.value === commande.id,
    command: () => openConfirmCommande(commande)
  },
  {
    label: 'Réceptionner',
    icon: 'pi pi-box',
    severity: 'success',
    visible: commande.status === 'confirmee',
    loading: receivingId.value === commande.id,
    command: () => openRecevoir(commande)
  },
  {
    label: 'Annuler',
    icon: 'pi pi-times',
    severity: 'danger',
    visible: canCancelCommande(commande.status),
    loading: pendingCommandeId.value === commande.id,
    command: () => cancelCommande(commande)
  }
]

const paiementRowActions = (paiement) => [
  {
    label: 'Annuler le paiement',
    icon: 'pi pi-times',
    severity: 'danger',
    visible: !paiement.is_cancelled,
    loading: pendingPaiementId.value === paiement.id,
    command: () => cancelPaiement(paiement)
  }
]

const tabFetchers = {
  commandes: () => fournisseursService.listCommandes(fournisseurId.value),
  dettes: () => fournisseursService.listDettes(fournisseurId.value, { status: 'all' }),
  paiements: () => fournisseursService.listPaiements(fournisseurId.value)
}

const invalidateTabs = (...tabs) => {
  tabs.forEach((tab) => loadedTabs.value.delete(tab))
}

const reloadTabs = async (...tabs) => {
  await Promise.all(tabs.map((tab) => loadTab(tab, { force: true })))
}

const loadTab = async (tab, { force = false } = {}) => {
  if (tab === 'info') {
    return
  }

  if (!force && loadedTabs.value.has(tab)) {
    return
  }

  const fetcher = tabFetchers[tab]
  if (!fetcher) {
    return
  }

  tabLoading[tab] = true
  try {
    tabData[tab] = await fetcher()
    loadedTabs.value.add(tab)
  } catch (error) {
    showError(error?.message || `Impossible de charger les ${tab}.`)
  } finally {
    tabLoading[tab] = false
  }
}

const onTabChange = (tab) => {
  loadTab(tab)
}

const openCreateCommande = () => {
  createCommandeVisible.value = true
}

const onCreateCommande = async (payload) => {
  if (creatingCommande.value) return
  creatingCommande.value = true
  try {
    await achatsService.create(payload)
    createCommandeVisible.value = false
    showSuccess('Commande achat créée.')
    invalidateTabs('commandes')
    await reloadTabs('commandes')
  } catch (error) {
    showError(error?.message || 'Impossible de créer la commande.')
  } finally {
    creatingCommande.value = false
  }
}

const openCommandeDetail = (commande) => {
  selectedCommande.value = commande
  commandeDetailVisible.value = true
}

const openConfirmCommande = (commande) => {
  selectedCommande.value = commande
  confirmVisible.value = true
}

const onConfirmCommande = async ({ expectedDate }) => {
  if (!selectedCommande.value?.id) {
    return
  }

  if (confirming.value) return
  confirming.value = true
  pendingCommandeId.value = selectedCommande.value.id
  try {
    const payload = expectedDate ? { expected_at: toIsoDateTime(expectedDate) } : {}
    await achatsService.confirm(selectedCommande.value.id, payload)
    confirmVisible.value = false
    showSuccess(`Commande ${selectedCommande.value.reference} confirmée.`)
    invalidateTabs('commandes')
    await reloadTabs('commandes')
  } catch (error) {
    showError(error?.message || 'La confirmation a échoué.')
  } finally {
    confirming.value = false
    pendingCommandeId.value = null
  }
}

const openRecevoir = (commande) => {
  selectedCommande.value = commande
  recevoirVisible.value = true
}

const onRecevoirConfirm = async ({ paid_amount, mode_de_paiement_id }) => {
  if (!selectedCommande.value?.id) {
    return
  }

  if (receiving.value) return
  receiving.value = true
  receivingId.value = selectedCommande.value.id
  try {
    await achatsService.recevoir(selectedCommande.value.id, {
      paid_amount,
      mode_de_paiement_id
    })
    recevoirVisible.value = false
    showSuccess('Commande réceptionnée.')
    invalidateTabs('commandes', 'dettes', 'paiements')
    await reloadTabs('commandes', 'dettes', 'paiements')
  } catch (error) {
    showError(error?.message || 'La réception a échoué.')
  } finally {
    receiving.value = false
    receivingId.value = null
  }
}

const cancelCommande = (commande) => {
  confirmRemoval({
    header: 'Annuler la commande',
    message: `Annuler la commande ${commande.reference} ? Cette action est définitive.`,
    onAccept: async () => {
      pendingCommandeId.value = commande.id
      try {
        await achatsService.cancel(commande.id)
        showSuccess(`Commande ${commande.reference} annulée.`)
        invalidateTabs('commandes')
        await reloadTabs('commandes')
      } catch (error) {
        showError(error?.message || "L'annulation a échoué.")
      } finally {
        pendingCommandeId.value = null
      }
    }
  })
}

const onCreateDette = async (payload) => {
  if (creatingDette.value) return
  creatingDette.value = true
  try {
    await dettesService.create(payload)
    createDetteVisible.value = false
    showSuccess('Dette enregistrée.')
    invalidateTabs('dettes')
    await reloadTabs('dettes')
  } catch (error) {
    showError(error?.message || "Impossible d'enregistrer la dette.")
  } finally {
    creatingDette.value = false
  }
}

const openDettePayment = (dette) => {
  paymentLoadingId.value = dette.id
  paymentDette.value = dette
  paymentVisible.value = true
  paymentLoadingId.value = null
}

const onPaymentConfirm = async ({ amount, mode_de_paiement_id, paymentDate }) => {
  if (!paymentDette.value?.id) {
    return
  }

  if (paying.value) return
  paying.value = true
  try {
    await dettesService.createPaiement({
      dette_fournisseur_id: paymentDette.value.id,
      amount: String(amount),
      mode_de_paiement_id,
      paid_at: toIsoDateTime(paymentDate)
    })
    paymentVisible.value = false
    showSuccess('Décaissement enregistré.')
    invalidateTabs('dettes', 'paiements')
    await reloadTabs('dettes', 'paiements')
  } catch (error) {
    showError(error?.message || 'Le décaissement a échoué.')
  } finally {
    paying.value = false
  }
}

const cancelPaiement = (paiement) => {
  confirmRemoval({
    header: 'Annuler le paiement',
    message: `Annuler le décaissement ${paiement.reference} ? La dette associée sera réouverte.`,
    onAccept: async () => {
      pendingPaiementId.value = paiement.id
      try {
        await dettesService.cancelPaiement(paiement.id)
        showSuccess(`Paiement ${paiement.reference} annulé.`)
        invalidateTabs('dettes', 'paiements')
        await reloadTabs('dettes', 'paiements')
      } catch (error) {
        showError(error?.message || "L'annulation a échoué.")
      } finally {
        pendingPaiementId.value = null
      }
    }
  })
}

watch(
  () => route.query,
  (query) => {
    if (query.tab && ['info', 'commandes', 'dettes', 'paiements'].includes(String(query.tab))) {
      activeTab.value = String(query.tab)
      loadTab(activeTab.value)
    }
    if (query.action === 'create-commande') {
      activeTab.value = 'commandes'
      loadTab('commandes')
      createCommandeVisible.value = true
    }
  },
  { immediate: true }
)

const load = async () => {
  loadError.value = null
  try {
    await fournisseursStore.fetchById(fournisseurId.value, { force: true })
  } catch (error) {
    loadError.value = error?.message || 'Fournisseur introuvable.'
  }
}

onMounted(load)
</script>

<style scoped>
.fournisseur-journal__header {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.fournisseur-journal__title-block {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}

.fournisseur-journal__title {
  margin: 0;
  font-size: 1.5rem;
}

.fournisseur-journal__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.fournisseur-journal__info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem 1.5rem;
}

.fournisseur-journal__label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.85rem;
  color: var(--layout-text-muted);
}

.fournisseur-journal__tab-toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 1rem;
}

.fournisseur-journal__actions {
  display: flex;
  justify-content: flex-end;
}
</style>
