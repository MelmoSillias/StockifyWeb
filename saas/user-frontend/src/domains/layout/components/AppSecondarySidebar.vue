<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  group: {
    type: Object,
    default: null
  },
  visible: {
    type: Boolean,
    default: false
  },
  activeKey: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['close'])

const router = useRouter()

const items = computed(() => props.group?.items || [])

const handleItemClick = (item) => {
  if (item.routeName) {
    router.push({ name: item.routeName })
  }
  emit('close')
}

const isActive = (item) => {
  return item.key === props.activeKey
}
</script>

<template>
  <Transition name="secondary-sidebar">
    <aside v-if="visible && group" class="app-secondary-sidebar">
      <div class="app-secondary-sidebar__header">
        <i v-if="group.icon" :class="['app-secondary-sidebar__icon', group.icon]"></i>
        <h3 class="app-secondary-sidebar__title">{{ group.label }}</h3>
      </div>
      <div class="app-secondary-sidebar__items">
        <button
          v-for="item in items"
          :key="item.key"
          type="button"
          class="app-secondary-sidebar__item"
          :class="{ 'app-secondary-sidebar__item--active': isActive(item) }"
          @click="handleItemClick(item)"
        >
          <i v-if="item.icon" :class="['app-secondary-sidebar__item-icon', item.icon]"></i>
          <span class="app-secondary-sidebar__item-label">{{ item.label }}</span>
        </button>
      </div>
    </aside>
  </Transition>
</template>
