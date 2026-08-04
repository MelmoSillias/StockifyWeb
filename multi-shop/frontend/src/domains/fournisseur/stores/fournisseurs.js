import { createCrudStore } from '@/domains/shared/stores/createCrudStore'
import { fournisseursService } from '@/domains/fournisseur/services/fournisseursService'

export const useFournisseursStore = createCrudStore('fournisseur-fournisseurs', fournisseursService)
