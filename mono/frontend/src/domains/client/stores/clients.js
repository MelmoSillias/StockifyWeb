import { createCrudStore } from '@/domains/shared/stores/createCrudStore'
import { clientsService } from '@/domains/client/services/clientsService'

export const useClientsStore = createCrudStore('client-clients', clientsService)
