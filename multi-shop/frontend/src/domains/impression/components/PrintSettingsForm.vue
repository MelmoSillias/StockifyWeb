<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'

import { usePermissions } from '@/domains/auth/composables/usePermissions'
import { usePrintSettingsStore } from '@/domains/impression/stores/printSettings'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useAsyncAction } from '@/domains/shared/composables/useAsyncAction'

const printSettingsStore = usePrintSettingsStore()
const { hasPermission } = usePermissions()
const { showError, showSuccess } = useEntityActions()

const canManage = computed(() => hasPermission('impression.settings.manage'))

const pageOptions = [
  { label: 'A4', value: 'a4' },
  { label: 'A5', value: 'a5' },
  { label: '80 mm (ticket)', value: 'receipt_80mm' }
]

const tablePageOptions = pageOptions.filter((o) => o.value !== 'receipt_80mm')

const exportOptions = [
  { label: 'PDF', value: 'pdf' },
  { label: 'Excel', value: 'excel' },
  { label: 'CSV', value: 'csv' },
  { label: 'Word', value: 'word' }
]

const form = reactive({
  shop_name: '',
  address_lines_text: '',
  phones_text: '',
  email: '',
  logo_url: '',
  default_page_table: 'a4',
  default_page_facture: 'a4',
  default_page_paiement: 'receipt_80mm',
  default_page_vente: 'receipt_80mm',
  default_page_bon_livraison: 'a4',
  default_page_transaction: 'a4',
  default_export_format: 'pdf',
  show_logo: true,
  footer_text: '',
  margin_mm: 10
})

const loadForm = (settings) => {
  if (!settings) {
    return
  }
  form.shop_name = settings.shop_name || ''
  form.address_lines_text = (settings.address_lines || []).join('\n')
  form.phones_text = (settings.phones || []).join('\n')
  form.email = settings.email || ''
  form.logo_url = settings.logo_url || ''
  form.default_page_table = settings.default_page_table
  form.default_page_facture = settings.default_page_facture
  form.default_page_paiement = settings.default_page_paiement
  form.default_page_vente = settings.default_page_vente
  form.default_page_bon_livraison = settings.default_page_bon_livraison
  form.default_page_transaction = settings.default_page_transaction
  form.default_export_format = settings.default_export_format
  form.show_logo = settings.show_logo
  form.footer_text = settings.footer_text || ''
  form.margin_mm = settings.margin_mm
}

onMounted(async () => {
  try {
    const settings = await printSettingsStore.fetchSettings()
    loadForm(settings)
  } catch (error) {
    showError(error?.message || 'Impossible de charger les réglages d\'impression.')
  }
})

const { pending: saving, run: save } = useAsyncAction(async () => {
  if (!canManage.value) {
    return
  }

  try {
    const payload = {
      shop_name: form.shop_name,
      address_lines: form.address_lines_text.split('\n').map((l) => l.trim()).filter(Boolean),
      phones: form.phones_text.split('\n').map((l) => l.trim()).filter(Boolean),
      email: form.email || null,
      logo_url: form.logo_url || null,
      default_page_table: form.default_page_table,
      default_page_facture: form.default_page_facture,
      default_page_paiement: form.default_page_paiement,
      default_page_vente: form.default_page_vente,
      default_page_bon_livraison: form.default_page_bon_livraison,
      default_page_transaction: form.default_page_transaction,
      default_export_format: form.default_export_format,
      show_logo: form.show_logo,
      footer_text: form.footer_text || null,
      margin_mm: form.margin_mm
    }
    await printSettingsStore.updateSettings(payload)
    showSuccess('Réglages d\'impression enregistrés.')
  } catch (error) {
    showError(error?.response?.data?.error || error?.message || 'Enregistrement impossible.')
  }
})
</script>

<template>
  <form class="print-settings-form" @submit.prevent="save">
    <section class="print-settings-form__section">
      <h3>En-tête boutique</h3>
      <div class="print-settings-form__grid">
        <label class="print-settings-form__field">
          <span>Nom affiché</span>
          <InputText v-model="form.shop_name" :disabled="!canManage" fluid />
        </label>
        <label class="print-settings-form__field">
          <span>Email</span>
          <InputText v-model="form.email" :disabled="!canManage" fluid />
        </label>
        <label class="print-settings-form__field print-settings-form__field--full">
          <span>Adresses (une par ligne)</span>
          <Textarea v-model="form.address_lines_text" :disabled="!canManage" rows="3" auto-resize fluid />
        </label>
        <label class="print-settings-form__field print-settings-form__field--full">
          <span>Téléphones (un par ligne)</span>
          <Textarea v-model="form.phones_text" :disabled="!canManage" rows="2" auto-resize fluid />
        </label>
        <label class="print-settings-form__field print-settings-form__field--full">
          <span>URL du logo</span>
          <InputText v-model="form.logo_url" :disabled="!canManage" fluid />
        </label>
        <label class="print-settings-form__field print-settings-form__field--inline">
          <span>Afficher le logo</span>
          <ToggleSwitch v-model="form.show_logo" :disabled="!canManage" />
        </label>
        <label class="print-settings-form__field print-settings-form__field--full">
          <span>Pied de page</span>
          <Textarea v-model="form.footer_text" :disabled="!canManage" rows="2" auto-resize fluid />
        </label>
      </div>
    </section>

    <section class="print-settings-form__section">
      <h3>Formats par défaut</h3>
      <div class="print-settings-form__grid">
        <label class="print-settings-form__field">
          <span>Tables / listes</span>
          <Select v-model="form.default_page_table" :options="tablePageOptions" option-label="label" option-value="value" :disabled="!canManage" fluid />
        </label>
        <label class="print-settings-form__field">
          <span>Factures & avoirs</span>
          <Select v-model="form.default_page_facture" :options="tablePageOptions" option-label="label" option-value="value" :disabled="!canManage" fluid />
        </label>
        <label class="print-settings-form__field">
          <span>Reçus paiement</span>
          <Select v-model="form.default_page_paiement" :options="pageOptions" option-label="label" option-value="value" :disabled="!canManage" fluid />
        </label>
        <label class="print-settings-form__field">
          <span>Tickets vente</span>
          <Select v-model="form.default_page_vente" :options="pageOptions" option-label="label" option-value="value" :disabled="!canManage" fluid />
        </label>
        <label class="print-settings-form__field">
          <span>Bons de livraison</span>
          <Select v-model="form.default_page_bon_livraison" :options="tablePageOptions" option-label="label" option-value="value" :disabled="!canManage" fluid />
        </label>
        <label class="print-settings-form__field">
          <span>Transactions</span>
          <Select v-model="form.default_page_transaction" :options="pageOptions" option-label="label" option-value="value" :disabled="!canManage" fluid />
        </label>
        <label class="print-settings-form__field">
          <span>Export par défaut</span>
          <Select v-model="form.default_export_format" :options="exportOptions" option-label="label" option-value="value" :disabled="!canManage" fluid />
        </label>
        <label class="print-settings-form__field">
          <span>Marges (mm)</span>
          <InputNumber v-model="form.margin_mm" :min="0" :max="30" :disabled="!canManage" fluid />
        </label>
      </div>
    </section>

    <div v-if="canManage" class="print-settings-form__actions">
      <Button type="submit" label="Enregistrer" icon="pi pi-save" :loading="saving" :disabled="saving" />
    </div>
  </form>
</template>

<style scoped>
.print-settings-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  min-width: 0;
}

.print-settings-form__section h3 {
  margin: 0 0 0.75rem;
  font-size: 1rem;
}

.print-settings-form__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem 1rem;
}

.print-settings-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 0;
}

.print-settings-form__field--full {
  grid-column: 1 / -1;
}

.print-settings-form__field--inline {
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
}

.print-settings-form__actions {
  display: flex;
  justify-content: flex-end;
}

@media (max-width: 767px) {
  .print-settings-form__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 360px) {
  .print-settings-form {
    gap: 1rem;
  }
}
</style>
