const DELIVERY_PENDING_STATUSES = ['confirmee', 'partiellement_livree']

export const startOfDay = (value) => {
  const date = value instanceof Date ? new Date(value) : new Date(value)
  if (Number.isNaN(date.getTime())) {
    return null
  }
  date.setHours(0, 0, 0, 0)
  return date
}

export const isOrderDeliveryDue = (order, referenceDate = new Date()) => {
  if (!order?.delivery_date || !DELIVERY_PENDING_STATUSES.includes(order.status)) {
    return false
  }

  const delivery = startOfDay(order.delivery_date)
  const today = startOfDay(referenceDate)

  if (!delivery || !today) {
    return false
  }

  return delivery <= today
}

export const sortOrdersByDeliveryPriority = (orders, referenceDate = new Date()) =>
  [...orders].sort((left, right) => {
    const leftDue = isOrderDeliveryDue(left, referenceDate)
    const rightDue = isOrderDeliveryDue(right, referenceDate)

    if (leftDue && !rightDue) {
      return -1
    }
    if (!leftDue && rightDue) {
      return 1
    }
    if (leftDue && rightDue) {
      return startOfDay(left.delivery_date) - startOfDay(right.delivery_date)
    }

    return new Date(right.created_at) - new Date(left.created_at)
  })
