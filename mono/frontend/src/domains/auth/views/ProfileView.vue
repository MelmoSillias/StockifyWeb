<script setup>
import { onMounted, ref } from 'vue'

import Card from 'primevue/card'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'

import ChangePasswordForm from '@/domains/auth/components/ChangePasswordForm.vue'
import LoginHistoryPanel from '@/domains/auth/components/LoginHistoryPanel.vue'
import ProfileAccountPanel from '@/domains/auth/components/ProfileAccountPanel.vue'
import ProfileSummaryCard from '@/domains/auth/components/ProfileSummaryCard.vue'
import { useAuthStore } from '@/domains/auth/stores/auth'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const authStore = useAuthStore()
const { showError } = useEntityActions()

const activeTab = ref('account')

onMounted(async () => {
  try {
    await authStore.fetchCurrentUser()
  } catch (error) {
    showError(error?.message || 'Impossible de charger votre profil.')
  }
})
</script>

<template>
  <section class="dashboard-page profile-page">
    <ProfileSummaryCard />

    <Card class="dashboard-panel">
      <template #content>
        <Tabs v-model:value="activeTab">
          <TabList>
            <Tab value="account">Compte</Tab>
            <Tab value="security">Sécurité</Tab>
            <Tab value="activity">Connexions</Tab>
          </TabList>

          <TabPanels>
            <TabPanel value="account">
              <ProfileAccountPanel />
            </TabPanel>
            <TabPanel value="security">
              <ChangePasswordForm />
            </TabPanel>
            <TabPanel value="activity">
              <LoginHistoryPanel />
            </TabPanel>
          </TabPanels>
        </Tabs>
      </template>
    </Card>
  </section>
</template>

<style scoped>
.profile-page {
  min-width: 0;
}

.profile-page :deep(.p-tablist) {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.profile-page :deep(.p-tablist::-webkit-scrollbar) {
  display: none;
}

@media (max-width: 360px) {
  .profile-page :deep(.p-tabpanels) {
    padding: 0.75rem 0.25rem;
  }
}
</style>
