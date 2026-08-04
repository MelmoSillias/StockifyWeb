import { computed, onMounted } from 'vue'

import { usePaymentMethodsStore } from '@/domains/finance/stores/paymentMethods'

export const usePaymentMethods = ({ encashmentOnly = false, autoLoad = true } = {}) => {
  const store = usePaymentMethodsStore()

  const load = async () => {
    await store.fetchAll()
  }

  if (autoLoad && !store.items.length) {
    onMounted(() => {
      load().catch(() => {})
    })
  }

  const methodOptions = computed(() => {
    const items = store.activeItems.filter((item) => !encashmentOnly || item.generates_transaction)

    return items.map((item) => ({
      label: item.label,
      value: item.id,
      code: item.code
    }))
  })

  const resolveMethodLabel = (codeOrId) => {
    if (!codeOrId) {
      return '—'
    }

    const byCode = store.byCode(codeOrId)
    if (byCode) {
      return byCode.label
    }

    const byId = store.items.find((item) => item.id === codeOrId)
    return byId?.label || codeOrId
  }

  return {
    store,
    loading: computed(() => store.loading),
    methodOptions,
    resolveMethodLabel,
    load
  }
}
