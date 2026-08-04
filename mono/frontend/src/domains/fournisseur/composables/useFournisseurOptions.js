import { ref } from 'vue'

import { fournisseursService } from '@/domains/fournisseur/services/fournisseursService'

export const useFournisseurOptions = () => {
  const options = ref([])
  const loading = ref(false)

  const load = async () => {
    loading.value = true

    try {
      const items = await fournisseursService.list()
      options.value = items.map((item) => ({
        label: item.name,
        value: item.id
      }))
      return options.value
    } finally {
      loading.value = false
    }
  }

  return { options, loading, load }
}
