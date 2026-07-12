<script setup>
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'

import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Popover from 'primevue/popover'

import { useLayoutStore } from '@/domains/layout/stores/layout'

const props = defineProps({
  brand: {
    type: Object,
    required: true
  },
  pageTitle: {
    type: String,
    required: true
  },
  pageSection: {
    type: String,
    required: true
  },
  user: {
    type: Object,
    default: null
  },
  displayName: {
    type: String,
    required: true
  },
  searchPlaceholder: {
    type: String,
    required: true
  },
  isDarkModeActive: {
    type: Boolean,
    required: true
  },
  darkMode: {
    type: String,
    required: true
  },
  showProfileActions: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['toggle-navigation', 'open-preferences', 'toggle-dark-mode', 'logout'])

const layoutStore = useLayoutStore()
const { topbarLogoVisibility, topbarProfilePosition, topbarSearchPosition } = storeToRefs(layoutStore)

const profileMenu = ref()

const userInitials = computed(() => {
  return props.displayName
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((value) => value[0]?.toUpperCase())
    .join('')
})

const showProfile = computed(() => props.showProfileActions && topbarProfilePosition.value !== 'hidden')
const showBrandLogo = computed(() => topbarLogoVisibility.value !== 'hidden')
const profileAtEnd = computed(() => topbarProfilePosition.value === 'end')
const showSearch = computed(() => topbarSearchPosition.value !== 'hidden')
const searchAtCenter = computed(() => topbarSearchPosition.value === 'center')

const toggleProfileMenu = (event) => {
  profileMenu.value.toggle(event)
}

const handleLogout = () => {
  profileMenu.value.hide()
  emit('logout')
}
</script>

<template>
  <div class="app-topbar-host">
    <header class="app-topbar">
      <div class="app-topbar__leading">
      <Button
        icon="pi pi-bars"
        severity="secondary"
        rounded
        text
        aria-label="Basculer la navigation"
        @click="$emit('toggle-navigation')"
      />

      <div class="app-topbar__brand">
        <div v-if="showBrandLogo" class="app-topbar__brand-mark">
          <img v-if="brand.logoUrl" :src="brand.logoUrl" :alt="brand.name" class="app-topbar__brand-image" />
          <span v-else>{{ brand.shortName }}</span>
        </div>
        <div>
          <p class="app-topbar__section">{{ pageSection }}</p>
          <h1 class="app-topbar__title">{{ pageTitle }}</h1>
        </div>
      </div>

      <!-- Search at Start -->
      <div v-if="showSearch && !searchAtCenter" class="app-topbar__search app-topbar__search--start">
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText :placeholder="searchPlaceholder" fluid />
        </IconField>
      </div>

        <!-- Profile at Start -->
        <div v-if="showProfile && !profileAtEnd" class="app-topbar__profile app-topbar__profile--start">
          <Avatar
            :image="user?.avatar"
            :label="userInitials"
            shape="circle"
            style="cursor: pointer"
            @click="toggleProfileMenu"
          />
          <div class="app-topbar__profile-copy" @click="toggleProfileMenu" style="cursor: pointer">
            <p>{{ displayName }}</p>
            <span>{{ user?.email || brand.tagline }}</span>
          </div>
        </div>
      </div>

      <!-- Search at Center -->
      <div v-if="showSearch && searchAtCenter" class="app-topbar__search app-topbar__search--center">
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText :placeholder="searchPlaceholder" fluid />
        </IconField>
      </div>

      <div class="app-topbar__actions">
        <Button
          :icon="isDarkModeActive ? 'pi pi-sun' : 'pi pi-moon'"
          severity="secondary"
          rounded
          text
          :aria-label="`Mode ${darkMode}`"
          @click="$emit('toggle-dark-mode')"
        />
        <Button
          icon="pi pi-sparkles"
          severity="secondary"
          rounded
          text
          aria-label="Ouvrir les preferences"
          @click="$emit('open-preferences')"
        />

        <!-- Profile at End -->
        <div v-if="showProfile && profileAtEnd" class="app-topbar__profile app-topbar__profile--end">
          <Avatar
            :image="user?.avatar"
            :label="userInitials"
            shape="circle"
            style="cursor: pointer"
            @click="toggleProfileMenu"
          />
          <div class="app-topbar__profile-copy" @click="toggleProfileMenu" style="cursor: pointer">
            <p>{{ displayName }}</p>
            <span>{{ user?.email || brand.tagline }}</span>
          </div>
        </div>
      </div>
    </header>

    <Popover v-if="showProfile" ref="profileMenu" class="app-profile-menu">
      <div class="app-profile-menu__content">
        <Button
          label="Déconnexion"
          icon="pi pi-sign-out"
          severity="danger"
          text
          fluid
          @click="handleLogout"
        />
      </div>
    </Popover>
  </div>
</template>
