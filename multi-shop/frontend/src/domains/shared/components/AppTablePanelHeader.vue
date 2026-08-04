<template>
  <div
    class="dashboard-panel__header"
    :class="{ 'dashboard-panel__header-actions--hide-create-mobile': hideCreateOnMobile }"
  >
    <div class="dashboard-panel__header-leading">
      <span class="dashboard-panel__title">{{ title }}</span>
      <Tag v-if="showCount && countLabel" :value="countLabel" icon="pi pi-chart-bar" severity="contrast" rounded />
    </div>

    <div class="dashboard-panel__header-actions">
      <IconField v-if="showSearch" class="dashboard-panel__search">
        <InputIcon class="pi pi-search" />
        <InputText
          :model-value="searchTerm"
          :placeholder="searchPlaceholder"
          fluid
          @update:model-value="$emit('update:searchTerm', $event)"
        />
      </IconField>

      <Button
        v-if="showReload"
        class="dashboard-panel__reload-button"
        icon="pi pi-refresh"
        text
        rounded
        severity="secondary"
        :loading="reloading"
        aria-label="Actualiser"
        v-tooltip.top="'Actualiser'"
        @click="$emit('reload')"
      />

      <slot name="actions" />

      <Button
        v-if="showCreate && createLabel"
        class="dashboard-panel__create-button"
        :label="createLabel"
        icon="pi pi-plus-circle"
        @click="$emit('create')"
      />
    </div>
  </div>
</template>

<script setup>
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'

defineProps({
  title: {
    type: String,
    required: true
  },
  countLabel: {
    type: String,
    default: ''
  },
  showCount: {
    type: Boolean,
    default: true
  },
  createLabel: {
    type: String,
    default: ''
  },
  showCreate: {
    type: Boolean,
    default: true
  },
  hideCreateOnMobile: {
    type: Boolean,
    default: false
  },
  searchTerm: {
    type: String,
    default: ''
  },
  searchPlaceholder: {
    type: String,
    default: 'Rechercher...'
  },
  showSearch: {
    type: Boolean,
    default: false
  },
  showReload: {
    type: Boolean,
    default: true
  },
  reloading: {
    type: Boolean,
    default: false
  }
})

defineEmits(['create', 'update:searchTerm', 'reload'])
</script>
