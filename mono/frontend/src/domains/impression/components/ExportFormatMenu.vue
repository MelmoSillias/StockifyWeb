<script setup>
import { ref } from 'vue'

import Button from 'primevue/button'
import Menu from 'primevue/menu'

const emit = defineEmits(['select'])

const menu = ref()

const items = [
  { label: 'PDF', icon: 'pi pi-file-pdf', command: () => emit('select', 'pdf') },
  { label: 'Excel', icon: 'pi pi-file-excel', command: () => emit('select', 'excel') },
  { label: 'CSV', icon: 'pi pi-table', command: () => emit('select', 'csv') },
  { label: 'Word', icon: 'pi pi-file-word', command: () => emit('select', 'word') }
]

const toggle = (event) => {
  menu.value.toggle(event)
}

defineExpose({ toggle })
</script>

<template>
  <Menu ref="menu" :model="items" popup />
</template>
