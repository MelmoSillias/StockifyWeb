<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'

import DatePicker from 'primevue/datepicker'
import Popover from 'primevue/popover'

const props = defineProps({
  variant: {
    type: String,
    default: 'chip',
    validator: (value) => ['chip', 'icon', 'panel'].includes(value)
  },
  compact: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['open-panel'])

const now = ref(new Date())
const calendarDate = ref(new Date())
const calendarPopover = ref()

let timerId = null

const timeFormatter = new Intl.DateTimeFormat('fr-FR', {
  hour: '2-digit',
  minute: '2-digit'
})

const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric'
})

const shortDateFormatter = new Intl.DateTimeFormat('fr-FR', {
  weekday: 'short',
  day: 'numeric',
  month: 'short'
})

const currentTime = computed(() => timeFormatter.format(now.value))
const currentDate = computed(() => dateFormatter.format(now.value))
const shortDate = computed(() => shortDateFormatter.format(now.value))

const chipClasses = computed(() => ({
  'app-topbar__chip--clickable': true,
  'app-topbar__chip--compact': props.compact
}))

const toggleCalendar = (event) => {
  calendarDate.value = new Date(now.value)
  calendarPopover.value.toggle(event)
}

const openQuickPanel = () => {
  emit('open-panel')
}

onMounted(() => {
  timerId = window.setInterval(() => {
    now.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (timerId !== null) {
    window.clearInterval(timerId)
  }
})
</script>

<template>
  <div v-if="variant === 'panel'" class="app-topbar-date-panel">
    <div class="app-topbar-date-panel__header">
      <div>
        <strong>{{ currentTime }}</strong>
        <span>{{ currentDate }}</span>
      </div>
    </div>
    <DatePicker v-model="calendarDate" inline class="app-topbar-date-panel__picker" />
  </div>

  <div v-else class="app-topbar__chip-host">
    <button
      v-if="variant === 'icon'"
      type="button"
      class="app-topbar__chip app-topbar__chip--icon app-topbar__chip--clickable"
      aria-label="Afficher le calendrier"
      @click="openQuickPanel"
    >
      <span class="app-topbar__chip-icon app-topbar__chip-icon--accent">
        <i class="pi pi-calendar" />
      </span>
    </button>

    <button
      v-else
      type="button"
      class="app-topbar__chip"
      :class="chipClasses"
      aria-label="Afficher le calendrier"
      @click="toggleCalendar"
    >
      <span class="app-topbar__chip-icon app-topbar__chip-icon--accent">
        <i class="pi pi-calendar" />
      </span>
      <span class="app-topbar__chip-copy">
        <strong class="app-topbar__chip-primary">{{ currentTime }}</strong>
        <span class="app-topbar__chip-secondary">{{ compact ? shortDate : currentDate }}</span>
      </span>
    </button>

    <Popover v-if="variant === 'chip'" ref="calendarPopover" class="app-topbar__calendar-popover">
      <DatePicker v-model="calendarDate" inline />
    </Popover>
  </div>
</template>
