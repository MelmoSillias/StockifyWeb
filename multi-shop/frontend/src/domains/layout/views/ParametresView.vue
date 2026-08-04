<template>
  <section class="dashboard-page parametres-page">
    <Card class="dashboard-panel">
      <template #content>
        <Tabs v-model:value="activeTab">
          <TabList>
            <Tab value="appearance">Apparence</Tab>
            <Tab value="impressions">Impressions</Tab>
          </TabList>

          <TabPanels>
            <TabPanel value="appearance">
              <div class="parametres-page__intro">
                <h2>Préférences d'interface</h2>
                <p>Thème, accent, densité et disposition de la navigation.</p>
              </div>
              <AppThemeControls />
            </TabPanel>
            <TabPanel value="impressions">
              <div class="parametres-page__intro">
                <h2>Réglages d'impression</h2>
                <p>En-tête boutique, formats de page et exports par défaut.</p>
              </div>
              <PrintSettingsForm v-if="hasPermission('impression.settings.view')" />
              <Message v-else severity="info">Vous n'avez pas accès aux réglages d'impression.</Message>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </template>
    </Card>
  </section>
</template>

<script setup>
import { ref } from 'vue'

import Card from 'primevue/card'
import Message from 'primevue/message'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'

import AppThemeControls from '@/domains/layout/components/AppThemeControls.vue'
import PrintSettingsForm from '@/domains/impression/components/PrintSettingsForm.vue'
import { usePermissions } from '@/domains/auth/composables/usePermissions'

const { hasPermission } = usePermissions()

const activeTab = ref('appearance')
</script>

<style scoped>
.parametres-page {
  min-width: 0;
}

.parametres-page :deep(.p-tablist) {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.parametres-page__intro {
  margin-bottom: 1rem;
}

.parametres-page__intro h2 {
  margin: 0 0 0.25rem;
  font-size: 1.125rem;
}

.parametres-page__intro p {
  margin: 0;
  color: var(--text-color-secondary, #64748b);
  font-size: 0.875rem;
}

@media (max-width: 360px) {
  .parametres-page :deep(.p-tabpanels) {
    padding: 0.75rem 0.25rem;
  }
}
</style>
