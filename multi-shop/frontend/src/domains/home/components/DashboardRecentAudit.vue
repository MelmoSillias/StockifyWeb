<template>
  <Card class="dashboard-panel dashboard-recent-audit dashboard-panel--flush">
    <template #title>
      <div class="dashboard-section-header">
        <div class="dashboard-section-header__leading">
          <span class="dashboard-section-header__icon">
            <i class="pi pi-shield"></i>
          </span>
          <div>
            <h2 class="dashboard-section-header__title">Journal d'audit récent</h2>
            <p class="dashboard-section-header__subtitle">Dernières actions enregistrées</p>
          </div>
        </div>
        <Button
          label="Voir le journal"
          icon="pi pi-arrow-right"
          icon-pos="right"
          text
          size="small"
          @click="router.push({ name: 'access-audit' })"
        />
      </div>
    </template>
    <template #content>
      <ul v-if="items.length > 0" class="dashboard-audit-list">
        <li v-for="item in items" :key="item.id" class="dashboard-audit-list__item">
          <div>
            <p class="dashboard-audit-list__action">{{ item.action }}</p>
            <p class="dashboard-audit-list__meta">
              {{ item.user_email || 'Système' }} · {{ formatDateTime(item.occurred_at) }}
            </p>
          </div>
          <Tag :value="item.status" :severity="statusSeverity(item.status)" rounded />
        </li>
      </ul>
      <div v-else class="dashboard-feed-empty">
        <span class="dashboard-feed-empty__icon">
          <i class="pi pi-history"></i>
        </span>
        <p>Aucune entrée d'audit récente.</p>
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
  recentAudit: {
    type: Object,
    default: null
  }
})

const router = useRouter()
const { formatDateTime } = useDisplayFormatters()

const items = computed(() => props.recentAudit?.items ?? [])

const statusSeverity = (status) => {
  if (status === 'success') {
    return 'success'
  }
  if (status === 'failure') {
    return 'danger'
  }

  return 'secondary'
}
</script>
