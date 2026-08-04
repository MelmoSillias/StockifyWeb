<template>
  <Card class="dashboard-panel dashboard-pending-supplier-orders dashboard-panel--flush">
    <template #title>
      <div class="dashboard-section-header">
        <div class="dashboard-section-header__leading">
          <span class="dashboard-section-header__icon">
            <i class="pi pi-inbox"></i>
          </span>
          <div>
            <h2 class="dashboard-section-header__title">Réceptions fournisseur en attente</h2>
            <p class="dashboard-section-header__subtitle">
              {{ items.length }} commande(s) à réceptionner
            </p>
          </div>
        </div>
        <Button
          label="Voir les fournisseurs"
          icon="pi pi-arrow-right"
          icon-pos="right"
          text
          size="small"
          @click="router.push({ name: 'fournisseurs' })"
        />
      </div>
    </template>
    <template #content>
      <ul v-if="items.length > 0" class="dashboard-delivery-list">
        <li
          v-for="item in items"
          :key="item.id"
          class="dashboard-delivery-list__item"
        >
          <div>
            <p class="dashboard-delivery-list__ref">{{ item.reference }}</p>
            <p class="dashboard-delivery-list__client">{{ item.fournisseur_name }}</p>
          </div>
          <div class="dashboard-delivery-list__meta">
            <span class="dashboard-delivery-list__amount">{{ formatMoney(item.total_amount) }}</span>
            <span class="dashboard-delivery-list__date">{{ formatDate(item.expected_at) }}</span>
            <Tag :value="statusLabel(item.status)" severity="info" rounded />
          </div>
        </li>
      </ul>
      <div v-else class="dashboard-feed-empty">
        <span class="dashboard-feed-empty__icon">
          <i class="pi pi-check-circle"></i>
        </span>
        <p>Aucune commande fournisseur en attente de réception.</p>
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
  pendingSupplierOrders: {
    type: Object,
    default: null
  }
})

const router = useRouter()
const { formatMoney, formatDate } = useDisplayFormatters()

const items = computed(() => props.pendingSupplierOrders?.items ?? [])

const statusLabel = (status) => {
  if (status === 'confirmee') {
    return 'Confirmée'
  }
  if (status === 'initiee') {
    return 'Initiée'
  }

  return status
}
</script>
