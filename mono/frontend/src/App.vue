<script setup>
import { RouterView } from 'vue-router'

import ConfirmDialog from 'primevue/confirmdialog'
import ConfirmPopup from 'primevue/confirmpopup'
import Toast from 'primevue/toast'

import { useLayoutTheme } from '@/domains/layout/composables/useLayoutTheme'

useLayoutTheme()
</script>

<template>
  <div>
    <ConfirmDialog />
    <ConfirmPopup />
    <Toast position="top-right" />
    <RouterView />
  </div>
</template>

