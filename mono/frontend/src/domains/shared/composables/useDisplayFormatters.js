const decimalFormatter = new Intl.NumberFormat('fr-FR', {
  minimumFractionDigits: 0,
  maximumFractionDigits: 20
})

const normalizeNumericValue = (value) => {
  if (value === null || value === undefined || value === '') {
    return null
  }

  const numericValue = Number(value)
  return Number.isFinite(numericValue) ? numericValue : null
}

export const useDisplayFormatters = () => {
  const dateTimeFormatter = new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short'
  })

  const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium'
  })

  const formatDateTime = (value) => {
    if (!value) {
      return 'Non planifiee'
    }

    const normalizedDate = new Date(value)
    return Number.isNaN(normalizedDate.getTime()) ? 'Date invalide' : dateTimeFormatter.format(normalizedDate)
  }

  const formatDate = (value) => {
    if (!value) {
      return 'Non renseignee'
    }

    const normalizedDate = new Date(value)
    return Number.isNaN(normalizedDate.getTime()) ? 'Date invalide' : dateFormatter.format(normalizedDate)
  }

  const formatDuration = (value) => {
    const numericValue = Number(value)

    if (!Number.isFinite(numericValue) || numericValue <= 0) {
      return 'Non renseignee'
    }

    return `${numericValue} min`
  }

  const joinLabels = (items, key = 'name') => {
    if (!Array.isArray(items) || items.length === 0) {
      return 'Aucun'
    }

    return items.map((item) => item?.[key]).filter(Boolean).join(', ')
  }

  const formatDecimal = (value, empty = '—') => {
    const numericValue = normalizeNumericValue(value)
    if (numericValue === null) {
      return typeof value === 'string' && value !== '' ? value : empty
    }

    return decimalFormatter.format(numericValue)
  }

  const formatCompactNumber = (value, empty = '—') => formatDecimal(value, empty)

  const formatMoney = (value) => {
    const numericValue = normalizeNumericValue(value) ?? 0
    return `${decimalFormatter.format(numericValue)} XOF`
  }

  const formatBuyerLabel = (acheteur) => {
    if (!acheteur) {
      return '—'
    }

    if (acheteur.client_name) {
      return acheteur.client_name
    }

    if (acheteur.anonymous_info) {
      return acheteur.anonymous_info
    }

    return '—'
  }

  return {
    formatDateTime,
    formatDate,
    formatDuration,
    formatDecimal,
    formatCompactNumber,
    formatMoney,
    formatBuyerLabel,
    joinLabels
  }
}
