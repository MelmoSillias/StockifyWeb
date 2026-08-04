<template>
  <div class="dashboard-period-toolbar">
    <div class="dashboard-period-toolbar__presets">
      <span class="dashboard-period-toolbar__label">Période</span>
      <SelectButton
        :model-value="preset === 'custom' ? null : preset"
        :options="selectablePresets"
        option-label="label"
        option-value="id"
        :allow-empty="false"
        @update:model-value="emit('update:preset', $event)"
      />
    </div>
    <div class="dashboard-period-toolbar__range">
      <label class="dashboard-period-toolbar__label" for="dashboard-date-range">Intervalle personnalisé</label>
      <DatePicker
        id="dashboard-date-range"
        :model-value="dateRange"
        selection-mode="range"
        date-format="dd/mm/yy"
        show-icon
        fluid
        hide-on-range-selection
        placeholder="Choisir une période"
        @update:model-value="emit('update:dateRange', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import DatePicker from 'primevue/datepicker'
import SelectButton from 'primevue/selectbutton'

const props = defineProps({
  preset: {
    type: String,
    required: true
  },
  dateRange: {
    type: Array,
    default: () => []
  },
  presets: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:preset', 'update:dateRange'])

const selectablePresets = computed(() =>
  props.presets.filter((entry) => entry.id !== 'custom')
)
</script>
