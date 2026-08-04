import { emptyToNull, createCrudService } from '@/domains/shared/services/createCrudService'

const serializeLot = (lot) => ({
  quantity: String(lot.quantity),
  unit_cost: Number(lot.unit_cost) === 0 ? '0.00' : String(lot.unit_cost),
  reference: emptyToNull(lot.reference),
  supplier_ref: emptyToNull(lot.supplier_ref),
  fournisseur_id: emptyToNull(lot.fournisseur_id),
  expiry_date: lot.expiry_date
    ? new Date(lot.expiry_date).toISOString().slice(0, 10)
    : null
})

const serializeProduct = (payload) => {
  const body = {
    name: emptyToNull(payload.name),
    reference: emptyToNull(payload.reference),
    description: emptyToNull(payload.description),
    category_id: emptyToNull(payload.category_id)
  }

  if (payload.unit_of_measure_id) {
    body.variant = {
      sku: payload.sku,
      unit_of_measure_id: payload.unit_of_measure_id,
      sale_mode: payload.sale_mode,
      default_price: emptyToNull(payload.default_price),
      alert_threshold: emptyToNull(payload.alert_threshold)
    }
  }

  if (Array.isArray(payload.lots) && payload.lots.length > 0) {
    body.lots = payload.lots.map(serializeLot)
  }

  return body
}

export const productsService = createCrudService({
  listPath: '/products',
  serialize: serializeProduct
})
