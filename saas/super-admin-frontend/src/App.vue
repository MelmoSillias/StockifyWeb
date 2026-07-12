<script setup>
import { RouterView } from 'vue-router'

import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'

import { useLayoutTheme } from '@/domains/layout/composables/useLayoutTheme'

useLayoutTheme()
</script>

<template>
  <div>
    <ConfirmDialog />
    <Toast position="top-right" />
    <RouterView />
  </div>
</template>

