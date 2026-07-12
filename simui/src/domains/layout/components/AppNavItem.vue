<script setup>
import { useRouter } from 'vue-router'

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  active: {
    type: Boolean,
    default: false
  },
  collapsed: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['click'])

const router = useRouter()

const handleClick = () => {
  if (props.item.routeName) {
    router.push({ name: props.item.routeName })
  }
  emit('click', props.item)
}
</script>

<template>
  <button
    type="button"
    class="app-nav-item"
    :class="{
      'app-nav-item--active': active,
      'app-nav-item--collapsed': collapsed
    }"
    @click="handleClick"
  >
    <i v-if="item.icon" :class="['app-nav-item__icon', item.icon]"></i>
    <span class="app-nav-item__label">{{ item.label }}</span>
    <span v-if="active" class="app-nav-item__active-bar"></span>
  </button>
</template>
