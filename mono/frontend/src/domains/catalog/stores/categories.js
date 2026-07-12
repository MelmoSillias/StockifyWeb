import { createCrudStore } from '@/domains/shared/stores/createCrudStore'
import { categoriesService } from '@/domains/catalog/services/categoriesService'

export const useCategoriesStore = createCrudStore('catalog-categories', categoriesService)
