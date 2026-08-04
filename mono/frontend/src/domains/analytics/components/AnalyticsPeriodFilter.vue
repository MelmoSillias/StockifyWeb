<template>
  <div class="analytics-period-toolbar">
    <div class="analytics-period-toolbar__presets">
      <span class="analytics-period-toolbar__label">Période</span>
      <SelectButton
        :model-value="preset === 'custom' ? null : preset"
        :options="selectablePresets"
        option-label="label"
        option-value="id"
        :allow-empty="false"
        @update:model-value="emit('update:preset', $event)"
      />
    </div>
    <div class="analytics-period-toolbar__range">
      <label class="analytics-period-toolbar__label" for="analytics-date-range">Intervalle</label>
      <DatePicker
        id="analytics-date-range"
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
    <div class="analytics-period-toolbar__compare">
      <ToggleSwitch
        :model-value="compareEnabled"
        input-id="analytics-compare"
        @update:model-value="emit('update:compareEnabled', $event)"
      />
      <label for="analytics-compare">Comparer période préc.</label>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import DatePicker from 'primevue/datepicker'
import SelectButton from 'primevue/selectbutton'
import ToggleSwitch from 'primevue/toggleswitch'

const props = defineProps({
  preset: { type: String, required: true },
  dateRange: { type: Array, default: () => [] },
  presets: { type: Array, default: () => [] },
  compareEnabled: { type: Boolean, default: true }
})

const emit = defineEmits(['update:preset', 'update:dateRange', 'update:compareEnabled'])

const selectablePresets = computed(() =>
  props.presets.filter((entry) => entry.id !== 'custom')
)
</script>
