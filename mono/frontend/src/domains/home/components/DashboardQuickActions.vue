<template>
  <div v-if="actions.length > 0" class="dashboard-home__quick-actions">
    <Button
      v-for="action in actions"
      :key="action.id"
      :label="action.label"
      :icon="action.icon"
      size="small"
      outlined
      @click="router.push({ name: action.routeName })"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import Button from 'primevue/button'

const props = defineProps({
  visibleActions: {
    type: Array,
    default: () => []
  }
})

const router = useRouter()

const actions = computed(() => props.visibleActions)
</script>
