<template>
  <div class="analytics-export-bar">
    <p class="analytics-export-bar__hint">
      <i class="pi pi-info-circle"></i>
      Export des données analytics (CSV)
    </p>
    <Button
      label="Exporter la section"
      icon="pi pi-download"
      severity="secondary"
      outlined
      size="small"
      :loading="exporting"
      @click="handleExport"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'

import Button from 'primevue/button'

const props = defineProps({
  sectionLabel: { type: String, default: 'Analytics' },
  data: { type: Object, default: () => ({}) }
})

const exporting = ref(false)

const flattenObject = (obj, prefix = '') => {
  const rows = []
  Object.entries(obj ?? {}).forEach(([key, value]) => {
    const label = prefix ? `${prefix}.${key}` : key
    if (value && typeof value === 'object' && !Array.isArray(value)) {
      rows.push(...flattenObject(value, label))
    } else if (Array.isArray(value)) {
      value.forEach((item, index) => {
        if (item && typeof item === 'object') {
          rows.push(...flattenObject(item, `${label}[${index}]`))
        } else {
          rows.push([`${label}[${index}]`, String(item ?? '')])
        }
      })
    } else {
      rows.push([label, String(value ?? '')])
    }
  })
  return rows
}

const handleExport = async () => {
  exporting.value = true
  try {
    const rows = [['Clé', 'Valeur'], ...flattenObject(props.data)]
    const csv = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(';')).join('\n')
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `analytics-${props.sectionLabel.toLowerCase().replace(/\s+/g, '-')}-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(url)
  } finally {
    exporting.value = false
  }
}
</script>
