<script setup>
import { computed, ref } from 'vue'

import Button from 'primevue/button'
import Menu from 'primevue/menu'

import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'

const props = defineProps({
  actions: {
    type: Array,
    required: true
  },
  ariaLabel: {
    type: String,
    default: 'Actions'
  }
})

const { isMobile } = useBreakpoint()
const menu = ref()

const visibleActions = computed(() => props.actions.filter((action) => action.visible !== false))

const menuItems = computed(() =>
  visibleActions.value.map((action) => ({
    label: action.label,
    icon: action.icon,
    disabled: Boolean(action.disabled || action.loading),
    class: action.severity === 'danger' ? 'app-table-actions-menu__danger' : undefined,
    command: action.command
  }))
)

const toggleMenu = (event) => {
  menu.value.toggle(event)
}
</script>

<template>
  <div class="actions-cell">
    <template v-if="isMobile">
      <Button
        icon="pi pi-ellipsis-v"
        text
        rounded
        size="small"
        :aria-label="ariaLabel"
        :disabled="visibleActions.length === 0"
        @click="toggleMenu"
      />
      <Menu ref="menu" :model="menuItems" popup class="app-table-actions-menu" />
    </template>
    <template v-else>
      <Button
        v-for="action in visibleActions"
        :key="action.label"
        :icon="action.icon"
        text
        rounded
        :severity="action.severity"
        :loading="action.loading"
        :disabled="action.disabled"
        v-tooltip.top="action.label"
        @click="action.command"
      />
    </template>
  </div>
</template>
