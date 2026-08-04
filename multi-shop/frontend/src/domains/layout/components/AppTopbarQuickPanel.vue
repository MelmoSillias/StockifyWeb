<script setup>
import Drawer from 'primevue/drawer'

import AppTopbarDateClock from '@/domains/layout/components/AppTopbarDateClock.vue'

const visible = defineModel({
  type: Boolean,
  required: true
})
</script>

<template>
  <Drawer
    :visible="visible"
    position="right"
    class="app-topbar-quick-panel"
    @update:visible="visible = $event"
  >
    <template #header>
      <div class="app-topbar-quick-panel__header">
        <p class="app-topbar-quick-panel__eyebrow">Accès rapide</p>
        <h2 class="app-topbar-quick-panel__title">Calendrier</h2>
      </div>
    </template>

    <div class="app-topbar-quick-panel__content">
      <AppTopbarDateClock variant="panel" />
    </div>
  </Drawer>
</template>
