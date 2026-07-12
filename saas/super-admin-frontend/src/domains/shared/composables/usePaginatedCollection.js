import { computed, ref, watch } from 'vue'

export const usePaginatedCollection = ({
  sourceItems,
  filterItem,
  watchSources = [],
  initialRows = 10,
  rowsPerPageOptions = [10, 20, 50]
}) => {
  const searchTerm = ref('')
  const first = ref(0)
  const rows = ref(initialRows)

  const normalizedSourceItems = computed(() => sourceItems.value || [])

  const filteredItems = computed(() => {
    const normalizedSearch = searchTerm.value.trim().toLowerCase()

    return normalizedSourceItems.value.filter((item) => filterItem(item, normalizedSearch))
  })

  const paginatedItems = computed(() => filteredItems.value.slice(first.value, first.value + rows.value))

  const resetPagination = () => {
    first.value = 0
  }

  const onPage = (event) => {
    first.value = event.first
    rows.value = event.rows
  }

  watch([searchTerm, ...watchSources], resetPagination, { deep: true })

  return {
    first,
    rows,
    rowsPerPageOptions,
    searchTerm,
    filteredItems,
    paginatedItems,
    totalItems: computed(() => normalizedSourceItems.value.length),
    filteredCount: computed(() => filteredItems.value.length),
    onPage,
    resetPagination
  }
}