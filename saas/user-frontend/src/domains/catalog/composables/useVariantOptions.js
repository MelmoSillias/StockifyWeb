import { ref } from 'vue'

import { useProductsStore } from '@/domains/catalog/stores/products'
import { variantsService } from '@/domains/catalog/services/variantsService'

export const useVariantOptions = () => {
  const options = ref([])
  const loading = ref(false)

  const load = async () => {
    loading.value = true
    const productsStore = useProductsStore()

    try {
      if (!productsStore.items.length) {
        await productsStore.fetchAll()
      }

      const entries = []
      for (const product of productsStore.items) {
        const variants = await variantsService.list(product.id)
        for (const variant of variants) {
          entries.push({
            label: `${variant.sku} — ${product.name}`,
            value: variant.id,
            sku: variant.sku,
            productId: product.id,
            productName: product.name
          })
        }
      }

      options.value = entries
      return entries
    } finally {
      loading.value = false
    }
  }

  return { options, loading, load }
}
