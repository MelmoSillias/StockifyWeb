import { ref } from 'vue'

import { variantsService } from '@/domains/catalog/services/variantsService'

export const useVariantOptions = () => {
  const options = ref([])
  const catalog = ref([])
  const loading = ref(false)

  const load = async () => {
    loading.value = true

    try {
      const entries = await variantsService.listCatalog()
      catalog.value = entries
      options.value = entries.map((entry) => ({
        label: entry.label,
        value: entry.id,
        sku: entry.sku,
        productId: entry.product_id,
        productName: entry.product_name,
        categoryId: entry.category_id,
        categoryName: entry.category_name,
        variantLabel: `${entry.unit_label} · ${entry.sale_mode}`,
        unitOfMeasureId: entry.unit_of_measure_id,
        saleMode: entry.sale_mode,
        available: entry.available,
        isLowStock: entry.is_low_stock
      }))
      return options.value
    } finally {
      loading.value = false
    }
  }

  return { options, catalog, loading, load }
}
