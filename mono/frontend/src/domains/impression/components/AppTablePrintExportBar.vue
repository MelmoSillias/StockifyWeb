<script setup>
import { computed, ref } from 'vue'

import Button from 'primevue/button'

import ExportFormatMenu from '@/domains/impression/components/ExportFormatMenu.vue'
import { useTableExport, useTablePrint } from '@/domains/impression/composables/usePrintDocument'
import { useAuthStore } from '@/domains/auth/stores/auth'

const props = defineProps({
  tableType: {
    type: String,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  columns: {
    type: Array,
    default: () => []
  },
  searchTerm: {
    type: String,
    default: ''
  },
  permissionPrint: {
    type: String,
    default: 'impression.documents.print'
  },
  permissionExport: {
    type: String,
    default: 'impression.tables.export'
  }
})

const exportMenu = ref()
const { printTable } = useTablePrint()
const { exportTable } = useTableExport()
const authStore = useAuthStore()

const canPrint = computed(() => authStore.hasPermission(props.permissionPrint))
const canExport = computed(() => authStore.hasPermission(props.permissionExport))

const payload = () => ({
  filters: props.filters,
  columns: props.columns,
  search: props.searchTerm
})

const onPrint = () => {
  if (!canPrint.value) {
    return
  }
  printTable(props.tableType, payload())
}

const onExportSelect = (format) => {
  if (!canExport.value) {
    return
  }
  exportTable(props.tableType, format, payload())
}

const openExportMenu = (event) => {
  exportMenu.value?.toggle(event)
}
</script>

<template>
  <div class="app-table-print-export-bar">
    <Button
      v-if="canPrint"
      icon="pi pi-print"
      text
      rounded
      size="small"
      severity="secondary"
      aria-label="Imprimer"
      v-tooltip.top="'Imprimer'"
      @click="onPrint"
    />
    <Button
      v-if="canExport"
      icon="pi pi-download"
      text
      rounded
      size="small"
      severity="secondary"
      aria-label="Exporter"
      v-tooltip.top="'Exporter'"
      @click="openExportMenu"
    />
    <ExportFormatMenu ref="exportMenu" @select="onExportSelect" />
  </div>
</template>

<style scoped>
.app-table-print-export-bar {
  display: inline-flex;
  align-items: center;
  gap: 0.125rem;
  flex-shrink: 0;
}

@media (max-width: 360px) {
  .app-table-print-export-bar :deep(.p-button) {
    width: 2rem;
    height: 2rem;
  }
}
</style>
