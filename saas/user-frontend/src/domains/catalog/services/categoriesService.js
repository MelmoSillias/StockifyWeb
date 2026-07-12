import { emptyToNull } from '@/domains/shared/services/createCrudService'
import { createShopScopedCrudService } from '@/domains/shared/services/createShopScopedService'

const serializeCategory = (payload) => ({
  name: emptyToNull(payload.name),
  parent_id: emptyToNull(payload.parent_id)
})

export const categoriesService = createShopScopedCrudService({
  resource: 'categories',
  serialize: serializeCategory
})
