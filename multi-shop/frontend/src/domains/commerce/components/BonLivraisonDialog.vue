<template>
  <Dialog
    :visible="visible"
    header="Bon de livraison"
    modal
    :style="{ width: 'min(44rem, 94vw)' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div v-if="order" class="bon-livraison-dialog">
      <p class="bon-livraison-dialog__ref">
        Commande <strong>{{ order.reference }}</strong>
      </p>

      <AppTableState
        :loading="loading"
        :error="error"
        :retrying="loading"
        :is-empty="!loading && existingBons.length === 0 && createLines.length === 0"
        empty-title="Rien à livrer"
        empty-text="Tous les articles de cette commande ont déjà été expédiés."
        @retry="loadData"
      >
        <section v-if="createLines.length > 0" class="bon-livraison-dialog__section">
          <h4 class="bon-livraison-dialog__heading">Nouvelle expédition</h4>
          <p class="bon-livraison-dialog__hint">
            Quantités préremplies avec le reste à livrer. Réduisez manuellement pour une livraison partielle.
          </p>

          <DataTable :value="createLines" size="small" striped-rows>
            <Column field="label" header="Article" />
            <Column header="Commandé" style="width: 90px">
              <template #body="{ data }">{{ formatCompactNumber(data.ordered_quantity) }}</template>
            </Column>
            <Column header="Déjà expédié" style="width: 110px">
              <template #body="{ data }">{{ formatCompactNumber(data.shipped_quantity) }}</template>
            </Column>
            <Column header="Reste" style="width: 90px">
              <template #body="{ data }">{{ formatCompactNumber(data.remaining_quantity) }}</template>
            </Column>
            <Column header="À expédier" style="width: 130px">
              <template #body="{ data }">
                <InputNumber
                  v-model="data.quantity"
                  :min="0"
                  :max="Number(data.remaining_quantity)"
                  :min-fraction-digits="0"
                  :max-fraction-digits="3"
                  mode="decimal"
                  fluid
                />
              </template>
            </Column>
          </DataTable>
        </section>

        <section v-if="existingBons.length > 0" class="bon-livraison-dialog__section">
          <h4 class="bon-livraison-dialog__heading">Bons existants</h4>
          <DataTable :value="existingBons" size="small" striped-rows :responsive-layout="tableLayout">
            <Column field="reference" header="Référence" />
            <Column header="Statut" style="width: 110px">
              <template #body="{ data }">
                <Tag :value="bonStatusLabel(data.status)" :severity="bonStatusSeverity(data.status)" rounded />
              </template>
            </Column>
            <Column v-if="!isMobile" header="Envoyé le" style="width: 130px">
              <template #body="{ data }">{{ formatDateTime(data.sent_at) }}</template>
            </Column>
            <Column header="Actions" style="width: 90px">
              <template #body="{ data }">
                <AppTableActionsMenu
                  :actions="bonRowActions(data)"
                  aria-label="Actions bon de livraison"
                />
              </template>
            </Column>
          </DataTable>
        </section>
      </AppTableState>
    </div>

    <template #footer>
      <Button
        icon="pi pi-refresh"
        text
        rounded
        severity="secondary"
        :loading="loading"
        aria-label="Actualiser"
        v-tooltip.top="'Actualiser'"
        @click="loadData"
      />
      <Button label="Fermer" severity="secondary" text @click="$emit('update:visible', false)" />
      <Button
        v-if="createLines.length > 0"
        label="Expédier"
        icon="pi pi-truck"
        :loading="submitting"
        :disabled="!canSubmit"
        @click="onSubmit"
      />
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Tag from 'primevue/tag'
import { computed, ref, watch } from 'vue'

import { commerceService } from '@/domains/commerce/services/commerceService'
import AppTableActionsMenu from '@/domains/shared/components/AppTableActionsMenu.vue'
import AppTableState from '@/domains/shared/components/AppTableState.vue'
import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const props = defineProps({
  visible: { type: Boolean, default: false },
  order: { type: Object, default: null }
})

const emit = defineEmits(['update:visible', 'created', 'delivered'])

const { formatCompactNumber, formatDateTime } = useDisplayFormatters()
const { showError, showSuccess } = useEntityActions()
const { isMobile } = useBreakpoint()
const tableLayout = computed(() => (isMobile.value ? 'stack' : 'scroll'))

const loading = ref(false)
const error = ref(null)
const submitting = ref(false)
const deliveringId = ref(null)
const createLines = ref([])
const existingBons = ref([])

const bonStatusMap = {
  envoye: { label: 'Envoyé', severity: 'warn' },
  delivre: { label: 'Délivré', severity: 'success' }
}

const canSubmit = computed(() =>
  createLines.value.some((line) => Number(line.quantity) > 0)
)

const bonStatusLabel = (status) => bonStatusMap[status]?.label || status
const bonStatusSeverity = (status) => bonStatusMap[status]?.severity || 'secondary'

const loadData = async () => {
  if (!props.order?.id) {
    return
  }

  loading.value = true
  error.value = null
  try {
    const [remaining, bons] = await Promise.all([
      commerceService.getResteALivrer(props.order.id),
      commerceService.listBonsLivraison(props.order.id)
    ])

    createLines.value = remaining.map((line) => ({
      ...line,
      quantity: Number(line.remaining_quantity)
    }))
    existingBons.value = bons
  } catch (err) {
    createLines.value = []
    existingBons.value = []
    error.value = err?.message || 'Impossible de charger les informations de livraison.'
    showError(error.value)
  } finally {
    loading.value = false
  }
}

const onSubmit = async () => {
  if (!props.order?.id || !canSubmit.value) {
    return
  }

  submitting.value = true
  try {
    const lines = createLines.value
      .filter((line) => Number(line.quantity) > 0)
      .map((line) => ({
        variant_id: line.variant_id,
        quantity: String(line.quantity)
      }))

    const result = await commerceService.createBonLivraison(props.order.id, { lines })
    emit('created', result)
    showSuccess(`Bon de livraison ${result.bon.reference} expédié.`)
    await loadData()
  } catch (error) {
    showError(error?.message || 'L\'expédition a échoué.')
  } finally {
    submitting.value = false
  }
}

const markDelivered = async (bon) => {
  deliveringId.value = bon.id
  try {
    const updated = await commerceService.delivrerBonLivraison(bon.id)
    emit('delivered', updated)
    showSuccess('Bon de livraison marqué comme délivré.')
    await loadData()
  } catch (error) {
    showError(error?.message || 'La mise à jour a échoué.')
  } finally {
    deliveringId.value = null
  }
}

const bonRowActions = (bon) => [
  {
    label: 'Marquer délivré',
    icon: 'pi pi-check',
    severity: 'success',
    visible: bon.status === 'envoye',
    loading: deliveringId.value === bon.id,
    command: () => markDelivered(bon)
  }
]

watch(
  () => props.visible,
  (isVisible) => {
    if (isVisible) {
      loadData()
    }
  }
)
</script>

<style scoped>
.bon-livraison-dialog {
  display: grid;
  gap: 1rem;
}

.bon-livraison-dialog__ref {
  margin: 0;
}

.bon-livraison-dialog__section {
  display: grid;
  gap: 0.75rem;
}

.bon-livraison-dialog__heading {
  margin: 0;
  font-size: 0.95rem;
}

.bon-livraison-dialog__hint {
  margin: 0;
  color: var(--pv-text-muted);
  font-size: 0.85rem;
}
</style>
