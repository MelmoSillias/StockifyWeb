import { usePrintSettingsStore } from '@/domains/impression/stores/printSettings'
import { impressionService } from '@/domains/impression/services/impressionService'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const openBlob = (blob, { filename, openInNewTab = true, download = false } = {}) => {
  const url = URL.createObjectURL(blob)

  if (download && filename) {
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    link.click()
  }

  if (openInNewTab) {
    window.open(url, '_blank', 'noopener,noreferrer')
  }

  setTimeout(() => URL.revokeObjectURL(url), 60_000)
}

const extensionForFormat = (format) => {
  const map = { html: 'html', pdf: 'pdf', excel: 'xlsx', csv: 'csv', word: 'docx' }
  return map[format] || format
}

export function usePrintDocument() {
  const printSettingsStore = usePrintSettingsStore()
  const { showError } = useEntityActions()

  const printDocument = async (type, id, options = {}) => {
    try {
      await printSettingsStore.fetchSettings()
      const page = options.page || printSettingsStore.defaultPageFor(type)
      const blob = await impressionService.fetchDocument(type, id, {
        format: 'html',
        page,
        disposition: 'inline'
      })
      openBlob(blob, { openInNewTab: true })
    } catch (error) {
      showError(error?.response?.data?.error || error?.message || 'Impossible d\'imprimer le document.')
    }
  }

  const exportDocument = async (type, id, format = 'pdf', options = {}) => {
    try {
      await printSettingsStore.fetchSettings()
      const page = options.page || printSettingsStore.defaultPageFor(type)
      const blob = await impressionService.fetchDocument(type, id, {
        format,
        page,
        disposition: format === 'html' ? 'inline' : 'attachment'
      })
      const filename = `${type}-${id.slice(0, 8)}.${extensionForFormat(format)}`
      openBlob(blob, {
        filename,
        openInNewTab: ['pdf', 'word', 'html'].includes(format),
        download: !['pdf', 'word', 'html'].includes(format)
      })
    } catch (error) {
      showError(error?.response?.data?.error || error?.message || 'Impossible d\'exporter le document.')
    }
  }

  return { printDocument, exportDocument }
}

export function useTablePrint() {
  const printSettingsStore = usePrintSettingsStore()
  const { showError } = useEntityActions()

  const printTable = async (tableType, { filters = {}, columns = [], search = '', page } = {}) => {
    try {
      await printSettingsStore.fetchSettings()
      const blob = await impressionService.printTable(tableType, {
        filters,
        columns,
        search,
        page: page || printSettingsStore.defaultPageFor('table')
      })
      openBlob(blob, { openInNewTab: true })
    } catch (error) {
      showError(error?.response?.data?.error || error?.message || 'Impossible d\'imprimer la liste.')
    }
  }

  return { printTable }
}

export function useTableExport() {
  const printSettingsStore = usePrintSettingsStore()
  const { showError, showSuccess } = useEntityActions()

  const exportTable = async (tableType, format, { filters = {}, columns = [], search = '', page } = {}) => {
    try {
      await printSettingsStore.fetchSettings()
      const resolvedFormat = format || printSettingsStore.defaultExportFormat
      const blob = await impressionService.exportTable(tableType, {
        format: resolvedFormat,
        filters,
        columns,
        search,
        page: page || printSettingsStore.defaultPageFor('table')
      })
      const filename = `${tableType}-${new Date().toISOString().slice(0, 10)}.${extensionForFormat(resolvedFormat)}`
      openBlob(blob, {
        filename,
        openInNewTab: ['pdf', 'word'].includes(resolvedFormat),
        download: true
      })
      showSuccess('Export généré avec succès.')
    } catch (error) {
      showError(error?.response?.data?.error || error?.message || 'Impossible d\'exporter la liste.')
    }
  }

  return { exportTable }
}
