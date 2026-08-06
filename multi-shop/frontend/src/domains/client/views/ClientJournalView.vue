<template>
  <section class="dashboard-page client-journal">
    <div class="client-journal__header">
      <Button
        label="Clientèle"
        icon="pi pi-arrow-left"
        text
        @click="router.push({ name: 'clients' })"
      />
      <div v-if="client" class="client-journal__title-block">
        <h1 class="client-journal__title">{{ client.name }}</h1>
        <div class="client-journal__meta">
          <Tag :value="statusLabel(client.status)" :severity="statusSeverity(client.status)" rounded />
          <Tag v-if="client.is_deleted" value="Archivé" severity="secondary" rounded />
        </div>
      </div>
    </div>

    <Card class="dashboard-panel">
      <template #title>
        <div class="client-journal__actions">
          <Button
            icon="pi pi-refresh"
            text
            rounded
            severity="secondary"
            :loading="clientsStore.detailLoading"
            aria-label="Actualiser"
            v-tooltip.top="'Actualiser'"
            @click="load()"
          />
        </div>
      </template>
      <template #content>
        <AppTableState
          :loading="clientsStore.detailLoading && !client"
          :error="loadError"
          :is-empty="!clientsStore.detailLoading && !client && Boolean(loadError)"
          empty-title="Client introuvable"
          :empty-text="loadError || 'Ce client n\'existe pas ou a été supprimé.'"
          @retry="load()"
        >
          <Tabs v-if="client" v-model:value="activeTab" @update:value="onTabChange">
            <TabList>
              <Tab value="info">Informations</Tab>
              <Tab value="ventes">Ventes</Tab>
              <Tab v-if="canViewOrders" value="commandes">Commandes</Tab>
              <Tab value="factures">Factures</Tab>
              <Tab value="creances">Créances</Tab>
              <Tab value="paiements">Paiements</Tab>
            </TabList>

            <TabPanels>
              <TabPanel value="info">
                <div class="client-journal__info-grid">
                  <div>
                    <span class="client-journal__label">Nom</span>
                    <strong>{{ client.name }}</strong>
                  </div>
                  <div>
                    <span class="client-journal__label">Téléphone</span>
                    <span>{{ client.phone || '—' }}</span>
                  </div>
                  <div>
                    <span class="client-journal__label">Email</span>
                    <span>{{ client.email || '—' }}</span>
                  </div>
                  <div>
                    <span class="client-journal__label">Statut</span>
                    <span>{{ statusLabel(client.status) }}</span>
                  </div>
                  <div>
                    <span class="client-journal__label">Plafond crédit</span>
                    <span>{{ client.credit_limit != null ? formatMoney(client.credit_limit) : '—' }}</span>
                  </div>
                  <div>
                    <span class="client-journal__label">Créé le</span>
                    <span>{{ formatDateTime(client.created_at) }}</span>
                  </div>
                  <div>
                    <span class="client-journal__label">Modifié le</span>
                    <span>{{ formatDateTime(client.updated_at) }}</span>
                  </div>
                </div>
              </TabPanel>

              <TabPanel value="ventes">
                <AppTableState
                  :loading="tabLoading.ventes"
                  :is-empty="!tabLoading.ventes && tabData.ventes.length === 0"
                  empty-title="Aucune vente"
                  empty-text="Ce client n'a pas encore de vente enregistrée."
                >
                  <DataTable
                    :value="tabData.ventes"
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
                    <Column header="Montant">
                      <template #body="{ data }">{{ formatMoney(data.total_amount) }}</template>
                    </Column>
                    <Column header="Statut">
                      <template #body="{ data }">
                        <Tag
                          :value="saleStatusLabel(data)"
                          :severity="saleStatusSeverity(data)"
                          rounded
                        />
                      </template>
                    </Column>
                    <Column header="Actions" style="width: 90px">
                      <template #body="{ data }">
                        <AppTableActionsMenu
                          :actions="venteRowActions(data)"
                          aria-label="Actions vente"
                        />
                      </template>
                    </Column>
                  </DataTable>
                </AppTableState>
              </TabPanel>

              <TabPanel v-if="canViewOrders" value="commandes">
                <AppTableState
                  :loading="tabLoading.commandes"
                  :is-empty="!tabLoading.commandes && tabData.commandes.length === 0"
                  empty-title="Aucune commande"
                  empty-text="Ce client n'a pas encore de commande enregistrée."
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
                    <Column v-if="!isMobile" header="Acompte">
                      <template #body="{ data }">{{ formatMoney(data.deposit_received) }}</template>
                    </Column>
                  </DataTable>
                </AppTableState>
              </TabPanel>

              <TabPanel value="factures">
                <AppTableState
                  :loading="tabLoading.factures"
                  :is-empty="!tabLoading.factures && tabData.factures.length === 0"
                  empty-title="Aucune facture"
                  empty-text="Ce client n'a pas encore de facture."
                >
                  <DataTable
                    :value="tabData.factures"
                    data-key="id"
                    striped-rows
                    :responsive-layout="tableLayout"
                    paginator
                    :rows="10"
                  >
                    <Column field="numero" header="Numéro" />
                    <Column v-if="!isMobile" header="Date">
                      <template #body="{ data }">{{ formatDateTime(data.issued_at) }}</template>
                    </Column>
                    <Column v-if="!isMobile" header="Origine">
                      <template #body="{ data }">{{ data.origin === 'vente' ? 'Vente' : 'Commande' }}</template>
                    </Column>
                    <Column header="Montant">
                      <template #body="{ data }">{{ formatMoney(data.total_amount) }}</template>
                    </Column>
                    <Column header="Statut">
                      <template #body="{ data }">
                        <Tag
                          :value="data.is_cancelled ? 'Annulée' : 'Active'"
                          :severity="data.is_cancelled ? 'danger' : 'success'"
                          rounded
                        />
                      </template>
                    </Column>
                    <Column header="Actions" style="width: 90px">
                      <template #body="{ data }">
                        <AppTableActionsMenu
                          :actions="factureRowActions(data)"
                          aria-label="Actions facture"
                        />
                      </template>
                    </Column>
                  </DataTable>
                </AppTableState>
              </TabPanel>

              <TabPanel value="creances">
                <CreancesTable
                  :items="tabData.creances"
                  :loading="tabLoading.creances"
                  :show-client-column="false"
                  :payment-loading-id="paymentLoadingId"
                  empty-text="Ce client n'a pas encore de créance enregistrée."
                  @pay="openCreancePayment"
                />
              </TabPanel>

              <TabPanel value="paiements">
                <AppTableState
                  :loading="tabLoading.paiements"
                  :is-empty="!tabLoading.paiements && tabData.paiements.length === 0"
                  empty-title="Aucun paiement"
                  empty-text="Ce client n'a pas encore de paiement enregistré."
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

    <SaleDetailDialog
      v-model:visible="saleDetailVisible"
      :sale="selectedSale"
    />

    <RecordPaymentDialog
      v-model:visible="paymentVisible"
      :balance="paymentCreance?.balance"
      :sale-date="paymentCreance?.issued_at"
      :loading="paying"
      @confirm="onPaymentConfirm"
    />
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
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

import SaleDetailDialog from '@/domains/commerce/components/SaleDetailDialog.vue'
import RecordPaymentDialog from '@/domains/commerce/components/RecordPaymentDialog.vue'
import { commerceService } from '@/domains/commerce/services/commerceService'
import CreancesTable from '@/domains/commerce/components/CreancesTable.vue'
import { creancesService } from '@/domains/commerce/services/creancesService'
import { clientsService } from '@/domains/client/services/clientsService'
import { useClientsStore } from '@/domains/client/stores/clients'
import { usePaymentMethods } from '@/domains/finance/composables/usePaymentMethods'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { usePrintDocument } from '@/domains/impression/composables/usePrintDocument'
import { usePermissions } from '@/domains/auth/composables/usePermissions'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { toIsoDateTime } from '@/domains/shared/services/createCrudService'

const route = useRoute()
const router = useRouter()
const clientsStore = useClientsStore()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))
const { formatDateTime, formatMoney } = useDisplayFormatters()
const { resolveMethodLabel: paymentMethodLabel } = usePaymentMethods()
const { showError, showSuccess } = useEntityActions()
const { printDocument } = usePrintDocument()
const { hasPermission, hasFeature } = usePermissions()
const canViewOrders = computed(() => hasFeature('stockify.orders'))

const activeTab = ref('info')
const loadError = ref(null)
const saleDetailVisible = ref(false)
const selectedSale = ref(null)
const paymentVisible = ref(false)
const paymentCreance = ref(null)
const paymentLoadingId = ref(null)
const paying = ref(false)

const tabData = reactive({
  ventes: [],
  commandes: [],
  factures: [],
  creances: [],
  paiements: []
})

const tabLoading = reactive({
  ventes: false,
  commandes: false,
  factures: false,
  creances: false,
  paiements: false
})

const loadedTabs = ref(new Set(['info']))

const clientId = computed(() => route.params.id)
const client = computed(() => clientsStore.currentItem)

const statusOptions = [
  { label: 'Actif', value: 'active' },
  { label: 'Suspendu', value: 'suspended' }
]

const statusLabel = (status) => statusOptions.find((option) => option.value === status)?.label || status
const statusSeverity = (status) => (status === 'suspended' ? 'warn' : 'success')

const orderStatusMap = {
  initiee: { label: 'Initiée', severity: 'secondary' },
  confirmee: { label: 'Confirmée', severity: 'success' },
  annulee: { label: 'Annulée', severity: 'danger' }
}

const orderStatusLabel = (status) => orderStatusMap[status]?.label || status
const orderStatusSeverity = (status) => orderStatusMap[status]?.severity || 'secondary'

const saleStatusLabel = (sale) => {
  if (sale.cancelled_at || sale.payment_status === 'annulee') {
    return 'Annulée'
  }
  if (sale.payment_status === 'paye') {
    return 'Payée'
  }
  if (sale.payment_status === 'partiellement_paye') {
    return 'Partiellement payée'
  }
  return 'Impayée'
}

const saleStatusSeverity = (sale) => {
  if (sale.cancelled_at || sale.payment_status === 'annulee') {
    return 'danger'
  }
  if (sale.payment_status === 'paye') {
    return 'success'
  }
  if (sale.payment_status === 'partiellement_paye') {
    return 'warn'
  }
  return 'danger'
}

const tabFetchers = {
  ventes: () => clientsService.listVentes(clientId.value),
  commandes: () => clientsService.listCommandes(clientId.value),
  factures: () => clientsService.listFactures(clientId.value),
  creances: () => creancesService.listByClient(clientId.value, { status: 'all' }),
  paiements: () => clientsService.listPaiements(clientId.value)
}

const loadTab = async (tab) => {
  if (tab === 'info' || loadedTabs.value.has(tab)) {
    return
  }

  if (tab === 'commandes' && !canViewOrders.value) {
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
  if (tab === 'commandes' && !canViewOrders.value) {
    activeTab.value = 'info'
    return
  }

  loadTab(tab)
}

const openSaleDetail = async (sale) => {
  try {
    selectedSale.value = await commerceService.getVente(sale.id)
  } catch {
    selectedSale.value = sale
  }
  saleDetailVisible.value = true
}

const venteRowActions = (sale) => [
  {
    label: 'Voir le détail',
    icon: 'pi pi-eye',
    command: () => openSaleDetail(sale)
  },
  {
    label: 'Imprimer ticket',
    icon: 'pi pi-print',
    visible: hasPermission('impression.documents.print'),
    command: () => printDocument('vente_ticket', sale.id)
  }
]

const factureRowActions = (facture) => [
  {
    label: 'Imprimer facture',
    icon: 'pi pi-print',
    visible: hasPermission('impression.documents.print'),
    command: () => printDocument('facture', facture.id)
  }
]

const paiementRowActions = (paiement) => [
  {
    label: 'Imprimer reçu',
    icon: 'pi pi-print',
    visible: hasPermission('impression.documents.print'),
    command: () => printDocument('paiement', paiement.id)
  }
]

const openCreancePayment = (creance) => {
  paymentLoadingId.value = creance.id
  paymentCreance.value = creance
  paymentVisible.value = true
  paymentLoadingId.value = null
}

const onPaymentConfirm = async ({ amount, mode_de_paiement_id, paymentDate }) => {
  if (!paymentCreance.value?.id) {
    return
  }

  if (paying.value) return
  paying.value = true
  try {
    await commerceService.createPaiement({
      facture_id: paymentCreance.value.id,
      amount: String(amount),
      mode_de_paiement_id,
      paid_at: toIsoDateTime(paymentDate)
    })
    paymentVisible.value = false
    showSuccess('Paiement enregistré.')
    loadedTabs.value.delete('creances')
    await loadTab('creances')
  } catch (error) {
    showError(error?.message || 'Le paiement a échoué.')
  } finally {
    paying.value = false
  }
}

const load = async () => {
  loadError.value = null
  try {
    await clientsStore.fetchById(clientId.value, { force: true })
  } catch (error) {
    loadError.value = error?.message || 'Client introuvable.'
  }
}

onMounted(load)
</script>

<style scoped>
.client-journal__header {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.client-journal__title-block {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}

.client-journal__title {
  margin: 0;
  font-size: 1.5rem;
}

.client-journal__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.client-journal__info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem 1.5rem;
}

.client-journal__label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.85rem;
  color: var(--p-text-muted-color);
}

.client-journal__actions {
  display: flex;
  justify-content: flex-end;
}
</style>
