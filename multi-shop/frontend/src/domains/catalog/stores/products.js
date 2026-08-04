import { createCrudStore } from '@/domains/shared/stores/createCrudStore'
import { productsService } from '@/domains/catalog/services/productsService'

export const useProductsStore = createCrudStore('catalog-products', productsService)
