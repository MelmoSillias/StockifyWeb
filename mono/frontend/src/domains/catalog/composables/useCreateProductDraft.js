import { reactive, watch } from 'vue'

const DRAFT_KEY = 'stockify.createProduct.draft'

export const createEmptyLot = () => ({
  quantity: null,
  unit_cost: null,
  reference: '',
  supplier_ref: '',
  expiry_date: null
})

export const createEmptyProductForm = () => ({
  name: '',
  reference: '',
  description: '',
  category_id: null,
  unit_of_measure_id: null,
  sale_mode: 'unit',
  default_price: null,
  alert_threshold: null,
  lots: []
})

const serializeDraft = (form) =>
  JSON.stringify({
    ...form,
    lots: (form.lots || []).map((lot) => ({
      ...lot,
      expiry_date: lot.expiry_date
        ? lot.expiry_date instanceof Date
          ? lot.expiry_date.toISOString()
          : lot.expiry_date
        : null
    }))
  })

const parseDraft = (raw) => {
  try {
    const parsed = JSON.parse(raw)
    return {
      ...createEmptyProductForm(),
      ...parsed,
      lots: Array.isArray(parsed.lots)
        ? parsed.lots.map((lot) => ({
            ...createEmptyLot(),
            ...lot,
            expiry_date: lot.expiry_date ? new Date(lot.expiry_date) : null
          }))
        : []
    }
  } catch {
    return null
  }
}

export const useCreateProductDraft = () => {
  const form = reactive(createEmptyProductForm())
  let ready = false

  const loadDraft = () => {
    if (typeof sessionStorage === 'undefined') {
      ready = true
      return false
    }

    const raw = sessionStorage.getItem(DRAFT_KEY)
    if (!raw) {
      ready = true
      return false
    }

    const draft = parseDraft(raw)
    if (!draft) {
      ready = true
      return false
    }

    Object.assign(form, draft)
    form.lots = draft.lots
    ready = true
    return true
  }

  const persistDraft = () => {
    if (!ready || typeof sessionStorage === 'undefined') {
      return
    }

    sessionStorage.setItem(DRAFT_KEY, serializeDraft(form))
  }

  const clearDraft = () => {
    if (typeof sessionStorage !== 'undefined') {
      sessionStorage.removeItem(DRAFT_KEY)
    }

    ready = false
    Object.assign(form, createEmptyProductForm())
    form.lots = []
    ready = true
  }

  watch(
    form,
    () => {
      persistDraft()
    },
    { deep: true }
  )

  return {
    form,
    loadDraft,
    persistDraft,
    clearDraft,
    createEmptyLot
  }
}
