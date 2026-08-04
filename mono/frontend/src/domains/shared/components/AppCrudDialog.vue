<template>
  <Dialog
    :visible="visible"
    modal
    class="crud-dialog"
    :style="{ width: 'min(920px, 94vw)' }"
    dismissable-mask
    @update:visible="$emit('update:visible', $event)"
  >
    <template #header>
      <div class="crud-dialog__header">
        <span class="crud-dialog__header-icon">
          <i class="pi pi-file-edit"></i>
        </span>
        <div class="crud-dialog__header-copy">
          <h2 class="crud-dialog__title">{{ title }}</h2>
          <p v-if="subtitle" class="crud-dialog__header-subtitle">{{ subtitle }}</p>
        </div>
      </div>
    </template>

    <div class="crud-dialog__content">
      <Message v-if="generalError" severity="error" :closable="false">{{ generalError }}</Message>

      <Fluid class="my-4">
        <div class="crud-dialog__grid">
        <div
          v-for="field in fields"
          :key="field.name"
          class="crud-dialog__field"
          :class="{ 'crud-dialog__field--full': field.fullWidth !== false }"
        >
          <label :for="field.name" class="crud-dialog__label">
            <i :class="field.icon || 'pi pi-angle-right'"></i>
            <span>{{ field.label }}</span>
          </label>

          <InputText
            v-if="field.type === 'text'"
            :id="field.name"
            :model-value="modelValue[field.name]"
            :placeholder="field.placeholder"
            :disabled="loading"
            :invalid="hasFieldError(field.name)"
            fluid
            @update:model-value="updateField(field.name, $event)"
          />

          <Password
            v-else-if="field.type === 'password'"
            :id="field.name"
            :model-value="modelValue[field.name]"
            :placeholder="field.placeholder"
            :feedback="false"
            toggle-mask
            :disabled="loading"
            :invalid="hasFieldError(field.name)"
            fluid
            @update:model-value="updateField(field.name, $event)"
          />

          <Textarea
            v-else-if="field.type === 'textarea'"
            :id="field.name"
            :model-value="modelValue[field.name]"
            :rows="field.rows || 4"
            :placeholder="field.placeholder"
            :disabled="loading"
            :invalid="hasFieldError(field.name)"
            auto-resize
            fluid
            @update:model-value="updateField(field.name, $event)"
          />

          <InputNumber
            v-else-if="field.type === 'number'"
            :id="field.name"
            :model-value="modelValue[field.name]"
            :min="field.min"
            :max="field.max"
            :min-fraction-digits="0"
            :max-fraction-digits="field.maxFractionDigits ?? 20"
            mode="decimal"
            :placeholder="field.placeholder"
            :disabled="loading"
            :invalid="hasFieldError(field.name)"
            fluid
            @update:model-value="updateField(field.name, $event)"
          />

          <div v-else-if="field.type === 'file'" class="crud-dialog__file-field">
            <input
              :id="field.name"
              class="crud-dialog__file-input"
              type="file"
              :accept="field.accept"
              :disabled="loading"
              @change="updateFileField(field, $event)"
            >
            <small v-if="modelValue[field.fileNameField || `${field.name}Name`]" class="crud-dialog__help">
              {{ modelValue[field.fileNameField || `${field.name}Name`] }}
            </small>
          </div>

          <DatePicker
            v-else-if="field.type === 'date'"
            :id="field.name"
            :model-value="modelValue[field.name]"
            :placeholder="field.placeholder"
            :show-time="field.showTime"
            :selection-mode="field.selectionMode"
            show-icon
            hour-format="24"
            :disabled="loading"
            :invalid="hasFieldError(field.name)"
            fluid
            @update:model-value="updateField(field.name, $event)"
          />

          <Select
            v-else-if="field.type === 'select'"
            :id="field.name"
            :model-value="modelValue[field.name]"
            :options="field.options"
            :option-label="field.optionLabel || 'label'"
            :option-value="field.optionValue || 'value'"
            :option-disabled="field.optionDisabled"
            :placeholder="field.placeholder"
            :disabled="loading"
            :invalid="hasFieldError(field.name)"
            filter
            fluid
            @update:model-value="updateField(field.name, $event)"
          />

          <MultiSelect
            v-else-if="field.type === 'multiselect'"
            :id="field.name"
            :model-value="modelValue[field.name]"
            :options="field.options"
            :option-label="field.optionLabel || 'label'"
            :option-value="field.optionValue || 'value'"
            :option-disabled="field.optionDisabled"
            :placeholder="field.placeholder"
            :disabled="loading"
            :invalid="hasFieldError(field.name)"
            display="chip"
            filter
            fluid
            @update:model-value="updateField(field.name, $event)"
          />

          <Chips
            v-else-if="field.type === 'chips'"
            :id="field.name"
            :model-value="modelValue[field.name]"
            :placeholder="field.placeholder"
            :disabled="loading"
            :invalid="hasFieldError(field.name)"
            fluid
            separator="," 
            @update:model-value="updateField(field.name, $event)"
          />

          <div v-else-if="field.type === 'switch'" class="crud-dialog__switch-row">
            <ToggleSwitch
              :input-id="field.name"
              :model-value="Boolean(modelValue[field.name])"
              :disabled="loading"
              @update:model-value="updateField(field.name, $event)"
            />
            <span>{{ field.description || field.placeholder }}</span>
          </div>

          <small v-if="field.helpText" class="crud-dialog__help">{{ field.helpText }}</small>
        </div>
        </div>
      </Fluid>
    </div>

    <template #footer>
        <div class="flex flex-row justify-end gap-2 mt-4">
            <Button label="Annuler" icon="pi pi-times" severity="secondary" text :disabled="loading" @click="$emit('update:visible', false)" />
            <Button :label="submitLabel" icon="pi pi-check" :loading="loading" :disabled="loading" @click="$emit('submit')" />
        </div>
    </template>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button'
import Chips from 'primevue/chips'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import Fluid from 'primevue/fluid'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import MultiSelect from 'primevue/multiselect'
import Password from 'primevue/password'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  },
  fields: {
    type: Array,
    default: () => []
  },
  modelValue: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  },
  submitLabel: {
    type: String,
    default: 'Enregistrer'
  },
  fieldErrors: {
    type: Object,
    default: () => ({})
  },
  generalError: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:visible', 'update:modelValue', 'submit'])

const hasFieldError = (fieldName) => {
  const error = props.fieldErrors[fieldName]
  if (Array.isArray(error)) {
    return error.length > 0
  }
  return Boolean(error)
}

const updateField = (fieldName, value) => {
  emit('update:modelValue', {
    ...props.modelValue,
    [fieldName]: value
  })
}

const updateFileField = (field, event) => {
  const input = event.target
  const file = input?.files?.[0]
  const fileNameField = field.fileNameField || `${field.name}Name`

  if (!file) {
    emit('update:modelValue', {
      ...props.modelValue,
      [field.name]: null,
      [fileNameField]: ''
    })
    return
  }

  const reader = new FileReader()
  reader.onload = () => {
    emit('update:modelValue', {
      ...props.modelValue,
      [field.name]: typeof reader.result === 'string' ? reader.result : null,
      [fileNameField]: file.name
    })
  }
  reader.readAsDataURL(file)
}
</script>

<style scoped>


.crud-dialog__content {
  display: grid;
  gap: 1rem;
}

.crud-dialog__header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.crud-dialog__header-icon {
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--pv-accent-soft) 80%, white);
  color: var(--pv-accent-strong);
  flex: 0 0 auto;
}

.crud-dialog__header-copy {
  display: grid;
  gap: 0.125rem;
}

.crud-dialog__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--pv-accent-strong);
}

.crud-dialog__header-subtitle {
  margin: 0;
  color: var(--pv-text-muted);
}

.crud-dialog__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.crud-dialog__field {
  display: grid;
  gap: 0.5rem;
}

.crud-dialog__field--full {
  grid-column: 1 / -1;
}

.crud-dialog__label {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-weight: 600;
}

.crud-dialog__help {
  color: var(--p-text-muted-color);
}

.crud-dialog__switch-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-height: 2.75rem;
}

.crud-dialog__file-field {
  display: grid;
  gap: 0.5rem;
}

.crud-dialog__file-input {
  width: 100%;
  padding: 0.8rem 0.95rem;
  border-radius: var(--p-border-radius-xl);
  border: 1px solid var(--p-content-border-color);
  background: var(--p-content-background);
  color: var(--p-text-color);
}

@media (max-width: 768px) {
  .crud-dialog__grid {
    grid-template-columns: 1fr;
  }
}
</style>