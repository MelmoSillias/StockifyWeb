<template>
  <Card class="dashboard-panel dashboard-pending-deliveries dashboard-panel--flush">
    <template #title>
      <div class="dashboard-section-header">
        <div class="dashboard-section-header__leading">
          <span class="dashboard-section-header__icon">
            <i class="pi pi-truck"></i>
          </span>
          <div>
            <h2 class="dashboard-section-header__title">Commandes à livrer</h2>
            <p class="dashboard-section-header__subtitle">
              {{ items.length }} commande(s) dans les 7 prochains jours
            </p>
          </div>
        </div>
        <Button
          label="Voir les commandes"
          icon="pi pi-arrow-right"
          icon-pos="right"
          text
          size="small"
          @click="router.push({ name: 'commerce-orders' })"
        />
      </div>
    </template>
    <template #content>
      <ul v-if="items.length > 0" class="dashboard-delivery-list">
        <li
          v-for="item in items"
          :key="item.id"
          class="dashboard-delivery-list__item"
          :class="{ 'dashboard-delivery-list__item--overdue': item.is_overdue }"
        >
          <div>
            <p class="dashboard-delivery-list__ref">{{ item.reference }}</p>
            <p class="dashboard-delivery-list__client">{{ buyerLabel(item) }}</p>
          </div>
          <div class="dashboard-delivery-list__meta">
            <span class="dashboard-delivery-list__amount">{{ formatMoney(item.total_amount) }}</span>
            <span class="dashboard-delivery-list__date">{{ formatDate(item.delivery_date) }}</span>
            <Tag
              v-if="item.is_overdue"
              value="En retard"
              severity="danger"
              rounded
            />
            <Tag
              v-else
              :value="statusLabel(item.status)"
              severity="warn"
              rounded
            />
          </div>
        </li>
      </ul>
      <div v-else class="dashboard-feed-empty">
        <span class="dashboard-feed-empty__icon">
          <i class="pi pi-check-circle"></i>
        </span>
        <p>Aucune commande à livrer dans les 7 prochains jours.</p>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'

import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  pendingDeliveries: {
    type: Object,
    default: null
  }
})

const router = useRouter()
const { formatMoney, formatDate } = useDisplayFormatters()

const items = computed(() => props.pendingDeliveries?.items ?? [])

const STATUS_LABELS = {
  confirmee: 'Confirmée',
  partiellement_livree: 'Part. livrée'
}

const buyerLabel = (item) => item.client_name || item.anonymous_info || 'Client anonyme'
const statusLabel = (status) => STATUS_LABELS[status] ?? status
</script>
