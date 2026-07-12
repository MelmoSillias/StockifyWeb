<template>
  <Toolbar class="entity-toolbar">
    <template #start>
      <div class="entity-toolbar__search-group">
        <IconField class="entity-toolbar__search-field">
          <InputIcon class="pi pi-search" />
          <InputText
            :model-value="searchTerm"
            :placeholder="searchPlaceholder"
            class="entity-toolbar__input"
            fluid
            @update:model-value="$emit('update:searchTerm', $event)"
          />
        </IconField>

        <DatePicker
          v-if="showDateFilters"
          :model-value="startDate"
          date-format="dd/mm/yy"
          show-icon
          placeholder="Date debut"
          input-class="entity-toolbar__input"
          fluid
          @update:model-value="$emit('update:startDate', $event)"
        />

        <DatePicker
          v-if="showDateFilters"
          :model-value="endDate"
          date-format="dd/mm/yy"
          show-icon
          placeholder="Date fin"
          input-class="entity-toolbar__input"
          fluid
          @update:model-value="$emit('update:endDate', $event)"
        />
      </div>
    </template>

    <template #end>
      <div class="entity-toolbar__actions">
        <Tag :value="countLabel" icon="pi pi-chart-bar" severity="contrast" rounded />
        <Button :label="createLabel" icon="pi pi-plus-circle" @click="$emit('create')" />
      </div>
    </template>
  </Toolbar>
</template>

<script setup>
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import Toolbar from 'primevue/toolbar'

defineProps({
  searchTerm: {
    type: String,
    default: ''
  },
  searchPlaceholder: {
    type: String,
    default: 'Rechercher...'
  },
  createLabel: {
    type: String,
    default: 'Ajouter'
  },
  countLabel: {
    type: String,
    default: '0 element'
  },
  showDateFilters: {
    type: Boolean,
    default: false
  },
  startDate: {
    type: [Date, String, null],
    default: null
  },
  endDate: {
    type: [Date, String, null],
    default: null
  }
})

defineEmits(['create', 'update:searchTerm', 'update:startDate', 'update:endDate'])
</script>

<style scoped>
.entity-toolbar {
  border-radius: 1rem;
}

.entity-toolbar__search-group,
.entity-toolbar__actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.entity-toolbar__search-field,
.entity-toolbar__input {
  min-width: 220px;
}

:deep(.p-toolbar) {
  padding: 1rem;
}
</style>