<template>
  <div class="dashboard-carousel-slide">
    <div class="dashboard-carousel-slide__header">
      <div class="dashboard-section-header__leading">
        <span class="dashboard-section-header__icon">
          <i :class="icon"></i>
        </span>
        <div>
          <h3 class="dashboard-section-header__title">{{ title }}</h3>
          <p class="dashboard-section-header__subtitle">{{ items.length }} élément(s)</p>
        </div>
      </div>
      <Button
        v-if="routeName"
        label="Voir tout"
        icon="pi pi-arrow-right"
        icon-pos="right"
        text
        size="small"
        @click="router.push({ name: routeName })"
      />
    </div>

    <ul v-if="items.length > 0" class="dashboard-carousel-slide__list">
      <li
        v-for="(item, index) in items"
        :key="itemKey(item)"
        class="dashboard-feed-item"
      >
        <span v-if="showRank" class="dashboard-feed-item__rank">{{ index + 1 }}</span>
        <div class="dashboard-feed-item__body">
          <p class="dashboard-feed-item__title">{{ itemTitle(item) }}</p>
          <p class="dashboard-feed-item__subtitle">{{ itemSubtitle(item) }}</p>
        </div>
        <div class="dashboard-feed-item__meta">
          <span v-if="itemMeta(item)" class="dashboard-feed-item__value">{{ itemMeta(item) }}</span>
          <Tag v-if="itemTag(item)" :value="itemTag(item).label" :severity="itemTag(item).severity" rounded />
        </div>
      </li>
    </ul>

    <div v-else class="dashboard-feed-empty">
      <span class="dashboard-feed-empty__icon">
        <i :class="icon"></i>
      </span>
      <p>{{ emptyText }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Tag from 'primevue/tag'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  icon: {
    type: String,
    required: true
  },
  items: {
    type: Array,
    default: () => []
  },
  type: {
    type: String,
    required: true
  },
  routeName: {
    type: String,
    default: ''
  },
  emptyText: {
    type: String,
    default: 'Aucune donnée pour cette période.'
  },
  formatMoney: {
    type: Function,
    required: true
  },
  formatDateTime: {
    type: Function,
    required: true
  },
  formatDecimal: {
    type: Function,
    required: true
  }
})

const router = useRouter()

const showRank = computed(() => props.type === 'top_products')

const ORDER_STATUS_LABELS = {
  initiee: 'Initiée',
  confirmee: 'Confirmée',
  partiellement_livree: 'Part. livrée',
  livree: 'Livrée',
  annulee: 'Annulée'
}

const MOVEMENT_TYPE_LABELS = {
  purchase: 'Achat',
  adjustment: 'Ajustement',
  transfer: 'Transfert',
  sale: 'Vente'
}

const itemKey = (item) => item.id ?? item.variant_id ?? item.reference ?? JSON.stringify(item)

const buyerLabel = (item) => item.client_name || item.anonymous_info || 'Client anonyme'

const itemTitle = (item) => {
  switch (props.type) {
    case 'recent_orders':
    case 'recent_sales':
      return item.reference
    case 'top_products':
      return item.label
    case 'recent_movements':
      return item.product_name
    case 'stock_alerts':
      return item.product_name
    default:
      return '—'
  }
}

const itemSubtitle = (item) => {
  switch (props.type) {
    case 'recent_orders':
      return `${buyerLabel(item)} · ${props.formatDateTime(item.created_at)}`
    case 'recent_sales':
      return `${buyerLabel(item)} · ${props.formatDateTime(item.created_at)}`
    case 'top_products':
      return `${props.formatDecimal(item.total_quantity)} vendu(s)`
    case 'recent_movements':
      return `${MOVEMENT_TYPE_LABELS[item.type] ?? item.type} · ${props.formatDateTime(item.occurred_at)}`
    case 'stock_alerts':
      return `${item.variant_label ?? item.sku} · Seuil ${props.formatDecimal(item.alert_threshold)}`
    default:
      return ''
  }
}

const itemMeta = (item) => {
  switch (props.type) {
    case 'recent_orders':
    case 'recent_sales':
      return props.formatMoney(item.total_amount)
    case 'top_products':
      return props.formatMoney(item.total_amount)
    case 'recent_movements':
      return props.formatDecimal(item.quantity)
    case 'stock_alerts':
      return props.formatDecimal(item.available)
    default:
      return ''
  }
}

const itemTag = (item) => {
  if (props.type === 'recent_orders') {
    return {
      label: ORDER_STATUS_LABELS[item.status] ?? item.status,
      severity: item.status === 'annulee' ? 'danger' : 'secondary'
    }
  }

  if (props.type === 'recent_movements') {
    return {
      label: item.direction === 'in' ? 'Entrée' : 'Sortie',
      severity: item.direction === 'in' ? 'success' : 'warn'
    }
  }

  if (props.type === 'stock_alerts') {
    return {
      label: 'Bas stock',
      severity: 'danger'
    }
  }

  return null
}
</script>
