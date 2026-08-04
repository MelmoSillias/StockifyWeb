<template>
  <div class="acheteur-selector" :class="{ 'acheteur-selector--compact': compact }">
    <div class="acheteur-selector__toggle">
      <SelectButton
        :model-value="modelValue.type"
        :options="typeOptions"
        option-label="label"
        option-value="value"
        :allow-empty="false"
        @update:model-value="onTypeChange"
      />
    </div>

    <div v-if="modelValue.type === 'client'" class="acheteur-selector__field">
      <Select
        :model-value="modelValue.clientId"
        :options="clients"
        option-label="name"
        option-value="id"
        placeholder="Choisir un client"
        filter
        show-clear
        fluid
        :loading="clientsLoading"
        @update:model-value="onClientChange"
      >
        <template #value="{ value, placeholder }">
          <span v-if="value" class="acheteur-selector__value" :title="clientLabel(value)">
            {{ clientLabel(value) }}
          </span>
          <span v-else class="acheteur-selector__placeholder">{{ placeholder }}</span>
        </template>
        <template #option="{ option }">
          <span class="acheteur-selector__option">{{ option.name }}</span>
        </template>
      </Select>
    </div>

    <div v-else class="acheteur-selector__field">
      <InputText
        :model-value="modelValue.anonymousInfo"
        placeholder="Nom / tel. (facultatif)"
        fluid
        @update:model-value="onAnonymousChange"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import SelectButton from 'primevue/selectbutton'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  },
  clients: {
    type: Array,
    default: () => []
  },
  clientsLoading: {
    type: Boolean,
    default: false
  },
  compact: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const typeOptions = [
  { label: 'Anonyme', value: 'anonymous' },
  { label: 'Client', value: 'client' }
]

const clientsById = computed(() => Object.fromEntries(props.clients.map((client) => [client.id, client])))

const clientLabel = (clientId) => clientsById.value[clientId]?.name || 'Client'

const onTypeChange = (type) => {
  emit('update:modelValue', { ...props.modelValue, type })
}

const onClientChange = (clientId) => {
  emit('update:modelValue', { ...props.modelValue, clientId })
}

const onAnonymousChange = (anonymousInfo) => {
  emit('update:modelValue', { ...props.modelValue, anonymousInfo })
}
</script>

<style scoped>
.acheteur-selector {
  display: grid;
  gap: 0.75rem;
  min-width: 0;
}

.acheteur-selector__field {
  min-width: 0;
}

.acheteur-selector__value,
.acheteur-selector__option {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
}

.acheteur-selector__placeholder {
  color: var(--pv-text-muted);
}

.acheteur-selector--compact {
  gap: 0.5rem;
}

.acheteur-selector--compact :deep(.p-selectbutton) {
  display: grid;
  grid-template-columns: 1fr 1fr;
  width: 100%;
}

.acheteur-selector--compact :deep(.p-togglebutton) {
  min-width: 0;
  padding: 0.4rem 0.35rem;
  font-size: 0.75rem;
}

.acheteur-selector--compact :deep(.p-select),
.acheteur-selector--compact :deep(.p-inputtext) {
  font-size: 0.8125rem;
}

@media (max-width: 767px) {
  .acheteur-selector {
    gap: 0.5rem;
  }

  .acheteur-selector :deep(.p-selectbutton) {
    display: grid;
    grid-template-columns: 1fr 1fr;
    width: 100%;
  }

  .acheteur-selector :deep(.p-togglebutton) {
    min-width: 0;
    padding: 0.4rem 0.35rem;
    font-size: 0.75rem;
  }
}
</style>
