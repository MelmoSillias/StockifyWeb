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

  return {
    formatDateTime,
    formatDate,
    formatDuration,
    joinLabels
  }
}