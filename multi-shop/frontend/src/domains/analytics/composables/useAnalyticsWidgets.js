import { computed } from 'vue'

import { usePermissions } from '@/domains/auth/composables/usePermissions'
import { ANALYTICS_KPI_DEFINITIONS, ANALYTICS_SECTION_DEFINITIONS } from '@/domains/analytics/config/analyticsSections'

export const useAnalyticsWidgets = () => {
  const { hasPermission } = usePermissions()

  const filterByPermission = (items) =>
    items.filter((item) => !item.permission || hasPermission(item.permission))

  const visibleSections = computed(() => filterByPermission(ANALYTICS_SECTION_DEFINITIONS))
  const visibleKpis = computed(() => filterByPermission(ANALYTICS_KPI_DEFINITIONS))

  const showSection = (sectionId) =>
    visibleSections.value.some((section) => section.id === sectionId)

  return {
    visibleSections,
    visibleKpis,
    showSection,
    hasPermission
  }
}
