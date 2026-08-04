import { emptyToNull, createCrudService } from '@/domains/shared/services/createCrudService'

const serializeCategory = (payload) => ({
  name: emptyToNull(payload.name),
  parent_id: emptyToNull(payload.parent_id)
})

export const categoriesService = createCrudService({
  listPath: '/categories',
  serialize: serializeCategory
})
