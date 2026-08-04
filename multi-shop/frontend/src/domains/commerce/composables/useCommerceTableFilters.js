export const startOfDay = (date) => {
  const value = new Date(date)
  value.setHours(0, 0, 0, 0)
  return value
}

export const endOfDay = (date) => {
  const value = new Date(date)
  value.setHours(23, 59, 59, 999)
  return value
}

export function matchesDateRange(isoDate, dateRange) {
  const [from, to] = dateRange || []
  if (!from && !to) {
    return true
  }

  const value = isoDate ? new Date(isoDate) : null
  if (!value || Number.isNaN(value.getTime())) {
    return false
  }

  if (from && value < startOfDay(from)) {
    return false
  }

  if (to && value > endOfDay(to)) {
    return false
  }

  return true
}

export function matchesSearch(item, term, getValues) {
  const normalized = term.trim().toLowerCase()
  if (!normalized) {
    return true
  }

  return getValues(item)
    .filter(Boolean)
    .some((value) => String(value).toLowerCase().includes(normalized))
}

/**
 * Hide cancelled items unless the filter explicitly targets them.
 * @param {boolean} isCancelled
 * @param {string|null|undefined} cancelledFilterValue - e.g. 'cancelled', 'annulee'
 */
export function matchesCancelledFilter(isCancelled, cancelledFilterValue) {
  const showCancelledOnly = cancelledFilterValue === 'cancelled' || cancelledFilterValue === 'annulee'
  if (showCancelledOnly) {
    return isCancelled
  }

  return !isCancelled
}
