<template>
  <section class="dashboard-page finances-page">
    <Card class="dashboard-panel">
      <template #content>
        <Tabs v-model:value="activeTab">
          <TabList>
            <Tab value="accounts">Comptes</Tab>
            <Tab value="transactions">Transactions</Tab>
            <Tab value="payment-methods">Modes de paiement</Tab>
          </TabList>

          <TabPanels>
            <TabPanel value="accounts">
              <AccountsTab @refresh="refreshAccounts" />
            </TabPanel>
            <TabPanel value="transactions">
              <TransactionsTab ref="transactionsTabRef" :accounts="accountsStore.items" @refresh-accounts="refreshAccounts" />
            </TabPanel>
            <TabPanel value="payment-methods">
              <PaymentMethodsTab :accounts="accountsStore.items" />
            </TabPanel>
          </TabPanels>
        </Tabs>
      </template>
    </Card>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'

import Card from 'primevue/card'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'

import AccountsTab from '@/domains/finance/components/AccountsTab.vue'
import PaymentMethodsTab from '@/domains/finance/components/PaymentMethodsTab.vue'
import TransactionsTab from '@/domains/finance/components/TransactionsTab.vue'
import { useAccountsStore } from '@/domains/finance/stores/accounts'
import { usePaymentMethodsStore } from '@/domains/finance/stores/paymentMethods'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'

const accountsStore = useAccountsStore()
const paymentMethodsStore = usePaymentMethodsStore()
const { showError } = useEntityActions()

const activeTab = ref('accounts')
const transactionsTabRef = ref(null)

const refreshAccounts = async () => {
  try {
    await accountsStore.fetchAll()
  } catch (error) {
    showError(error?.message || 'Impossible de charger les comptes.')
  }
}

onMounted(async () => {
  try {
    await Promise.all([accountsStore.fetchAll(), paymentMethodsStore.fetchAll()])
  } catch (error) {
    showError(error?.message || 'Impossible de charger les finances.')
  }
})
</script>

<style scoped>
.finances-page {
  min-width: 0;
}

.finances-page :deep(.p-tablist) {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.finances-page :deep(.p-tablist::-webkit-scrollbar) {
  display: none;
}

@media (max-width: 767px) {
  .finances-page > .dashboard-panel > .p-card-body > .p-card-content {
    padding: 0;
  }

  .finances-page :deep(.p-tablist) {
    padding: 0.65rem 0.5rem 0;
  }

  .finances-page :deep(.p-tabpanels) {
    padding-top: 0.5rem;
  }

  .finances-page :deep(.p-tabpanel) {
    padding: 0;
    min-width: 0;
  }
}

@media (max-width: 360px) {
  .finances-page :deep(.p-tablist) {
    padding-inline: 0.35rem;
  }

  .finances-page :deep(.p-tabpanels) {
    padding-top: 0.35rem;
  }
}
</style>
