<template>
  <section v-if="widgets.length > 0" class="dashboard-home__finance">
    <Card
      v-for="widget in widgets"
      :key="widget.id"
      class="dashboard-panel dashboard-finance-widget"
    >
      <template #title>
        <div class="dashboard-section-header">
          <div class="dashboard-section-header__leading">
            <span class="dashboard-section-header__icon">
              <i :class="widget.icon"></i>
            </span>
            <div>
              <h2 class="dashboard-section-header__title">{{ widget.title }}</h2>
              <p class="dashboard-section-header__subtitle">{{ widget.subtitle }}</p>
            </div>
          </div>
          <Button
            label="Voir"
            icon="pi pi-arrow-right"
            icon-pos="right"
            text
            size="small"
            @click="router.push({ name: widget.routeName })"
          />
        </div>
      </template>
      <template #content>
        <p class="dashboard-finance-widget__value">{{ widget.value }}</p>
        <ul v-if="widget.items.length > 0" class="dashboard-finance-widget__list">
          <li v-for="item in widget.items" :key="item.id" class="dashboard-finance-widget__item">
            <span class="dashboard-finance-widget__item-label">{{ item.label }}</span>
            <span class="dashboard-finance-widget__item-value">{{ formatMoney(item.amount) }}</span>
          </li>
        </ul>
        <p v-else class="dashboard-finance-widget__empty">{{ widget.emptyText }}</p>
      </template>
    </Card>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'

import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  financeSummary: {
    type: Object,
    default: null
  },
  visibleWidgets: {
    type: Array,
    default: () => []
  }
})

const router = useRouter()
const { formatMoney } = useDisplayFormatters()

const widgets = computed(() => props.visibleWidgets.map((definition) => {
  const data = props.financeSummary?.[definition.summaryKey] ?? null

  if (!data) {
    return {
      id: definition.id,
      title: definition.title,
      icon: definition.icon,
      routeName: definition.routeName,
      subtitle: 'Chargement…',
      value: '—',
      items: [],
      emptyText: 'Aucune donnée disponible.'
    }
  }

  if (definition.id === 'treasury') {
    return {
      id: definition.id,
      title: definition.title,
      icon: definition.icon,
      routeName: definition.routeName,
      subtitle: `${data.account_count ?? 0} compte(s) actif(s)`,
      value: formatMoney(data.total_balance ?? '0'),
      items: (data.accounts ?? []).slice(0, 5).map((account) => ({
        id: account.id,
        label: account.name,
        amount: account.balance
      })),
      emptyText: 'Aucun compte actif.'
    }
  }

  return {
    id: definition.id,
    title: definition.title,
    icon: definition.icon,
    routeName: definition.routeName,
    subtitle: `${data.count ?? 0} dossier(s) ouvert(s)`,
    value: formatMoney(data.total_balance ?? '0'),
    items: (data.top_items ?? []).slice(0, 5).map((item) => ({
      id: item.id,
      label: item.label,
      amount: item.balance
    })),
    emptyText: definition.id === 'client_creances'
      ? 'Aucune créance ouverte.'
      : 'Aucune dette ouverte.'
  }
}))
</script>
