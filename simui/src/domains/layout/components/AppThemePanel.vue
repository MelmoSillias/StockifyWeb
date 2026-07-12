<script setup>
import { ref } from 'vue'

import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Drawer from 'primevue/drawer'

import AppThemeControls from '@/domains/layout/components/AppThemeControls.vue'

const visible = defineModel({
  type: Boolean,
  required: true
})

const dialogVisible = ref(false)

const openDialogMode = () => {
  dialogVisible.value = true
}
</script>

<template>
  <div class="app-theme-panel-host">
    <Drawer :visible="visible" position="right" class="app-theme-panel" @update:visible="visible = $event">
      <template #header>
        <div class="app-theme-panel__header">
          <div>
            <p class="app-theme-panel__eyebrow">Preferences de personnalisation</p>
            <h2 class="app-theme-panel__title">Piloter l'experience</h2>
            <p class="app-theme-panel__subtitle">Theme, typo, accent et positionnement sur toute l'app.</p>
          </div>
          <Button label="Ouvrir en dialogue" icon="pi pi-external-link" severity="secondary" outlined @click="openDialogMode" />
        </div>
      </template>

      <div class="app-theme-panel__content">
        <AppThemeControls compact />

        <div class="app-theme-panel__footer">
          <Button label="Ouvrir en dialogue" icon="pi pi-window-maximize" severity="secondary" outlined @click="openDialogMode" />
          <Button label="Fermer" severity="secondary" @click="visible = false" />
        </div>
      </div>
    </Drawer>

    <Dialog :visible="dialogVisible" modal maximizable dismissableMask class="app-theme-dialog" header="Preferences globales" @update:visible="dialogVisible = $event">
      <AppThemeControls />
    </Dialog>
  </div>
</template>
