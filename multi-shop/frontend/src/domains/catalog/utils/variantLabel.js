const SALE_MODE_LABELS = {
  unit: 'Unité',
  weight: 'Poids',
  volume: 'Volume',
  bundle: 'Lot'
}

export const saleModeOptions = [
  { label: 'Unité', value: 'unit' },
  { label: 'Poids', value: 'weight' },
  { label: 'Volume', value: 'volume' },
  { label: 'Lot', value: 'bundle' }
]

export const slugify = (value) => {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toUpperCase()
    .replace(/[^A-Z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 32) || 'PROD'
}

export const buildVariantSku = ({ reference, name, unitCode, saleMode }) => {
  const prefix = slugify(reference || name)
  const unit = slugify(unitCode || 'unit')
  const mode = slugify(saleMode || 'unit')
  return `${prefix}-${unit}-${mode}`
}

export const saleModeLabel = (saleMode) => SALE_MODE_LABELS[saleMode] || saleMode || '—'

export const variantDisplayLabel = (variant, units = []) => {
  const unit = units.find((entry) => entry.id === variant.unit_of_measure_id)
  const unitPart = unit?.label || unit?.code || '—'
  return `${unitPart} · ${saleModeLabel(variant.sale_mode)}`
}
