export const matchesFinanceDateRange = (value, from, to) => {
  if (!value) {
    return false
  }

  const date = new Date(value).getTime()
  if (Number.isNaN(date)) {
    return false
  }

  if (from) {
    const fromTime = new Date(from).setHours(0, 0, 0, 0)
    if (date < fromTime) {
      return false
    }
  }

  if (to) {
    const toTime = new Date(to).setHours(23, 59, 59, 999)
    if (date > toTime) {
      return false
    }
  }

  return true
}

export const accountTypeLabel = (type) => {
  const labels = {
    caisse: 'Caisse',
    banque: 'Banque'
  }
  return labels[type] || type
}

export const transactionTypeLabel = (type) => {
  const labels = {
    revenu: 'Revenu',
    depense: 'Dépense'
  }
  return labels[type] || type
}

export const transactionSourceLabel = (sourceType) => {
  const labels = {
    paiement: 'Paiement',
    manuel: 'Manuel'
  }
  return labels[sourceType] || sourceType
}
