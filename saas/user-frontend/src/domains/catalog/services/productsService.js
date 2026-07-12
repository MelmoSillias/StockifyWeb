import { emptyToNull } from '@/domains/shared/services/createCrudService'
import { createShopScopedCrudService } from '@/domains/shared/services/createShopScopedService'

const serializeProduct = (payload) => ({
  name: emptyToNull(payload.name),
  reference: emptyToNull(payload.reference),
  description: emptyToNull(payload.description),
  category_id: emptyToNull(payload.category_id)
})

export const productsService = createShopScopedCrudService({
  resource: 'products',
  serialize: serializeProduct
})
