<script setup>
import { computed } from 'vue'
import { RouterView } from 'vue-router'

import ConfirmDialog from 'primevue/confirmdialog'
import ConfirmPopup from 'primevue/confirmpopup'
import Toast from 'primevue/toast'

import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useLayoutTheme } from '@/domains/layout/composables/useLayoutTheme'

useLayoutTheme()

const { isMobile } = useBreakpoint()
const toastPosition = computed(() => (isMobile.value ? 'top-center' : 'top-right'))
</script>

<template>
  <div>
    <ConfirmDialog />
    <ConfirmPopup />
    <Toast :position="toastPosition" class="app-toast" />
    <RouterView />
  </div>
</template>

