import { defineStore } from 'pinia'

import { impressionService } from '@/domains/impression/services/impressionService'

export const usePrintSettingsStore = defineStore('printSettings', {
  state: () => ({
    settings: null,
    loading: false,
    loaded: false
  }),

  getters: {
    defaultPageFor: (state) => (type) => {
      const map = {
        table: state.settings?.default_page_table,
        facture: state.settings?.default_page_facture,
        avoir: state.settings?.default_page_facture,
        paiement: state.settings?.default_page_paiement,
        vente_ticket: state.settings?.default_page_vente,
        bon_livraison: state.settings?.default_page_bon_livraison,
        transaction: state.settings?.default_page_transaction
      }
      return map[type] || 'a4'
    },
    defaultExportFormat: (state) => state.settings?.default_export_format || 'pdf',
    printProfile: (state) => state.settings
  },

  actions: {
    async fetchSettings(force = false) {
      if (this.loaded && !force) {
        return this.settings
      }

      this.loading = true
      try {
        this.settings = await impressionService.getSettings()
        this.loaded = true
        return this.settings
      } finally {
        this.loading = false
      }
    },

    async updateSettings(payload) {
      this.settings = await impressionService.updateSettings(payload)
      this.loaded = true
      return this.settings
    }
  }
})
