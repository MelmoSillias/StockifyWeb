<script setup>
import { computed } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'

import Breadcrumb from 'primevue/breadcrumb'
import { useToast } from 'primevue/usetoast'

import AppSidebar from '@/domains/layout/components/AppSidebar.vue'
import AppThemePanel from '@/domains/layout/components/AppThemePanel.vue'
import AppTopbar from '@/domains/layout/components/AppTopbar.vue'
import { useLayout } from '@/domains/layout/composables/useLayout'
import { useLayoutTheme } from '@/domains/layout/composables/useLayoutTheme'
import { useAuthStore } from '@/domains/auth/stores/auth'
import { useLayoutStore } from '@/domains/layout/stores/layout'
import { appConfig } from '@/config/app'

const authStore = useAuthStore()
const layoutStore = useLayoutStore()
const router = useRouter()
const toast = useToast()

const { motionPreset } = storeToRefs(layoutStore)

const {
  brand,
  shellConfig,
  menuModel,
  pageTitle,
  pageSection,
  homeBreadcrumb,
  breadcrumbs,
  sidebarMode,
  sidebarCollapsed,
  mobileSidebarOpen,
  preferencesOpen,
  handlePrimaryNavigation,
  setMobileSidebarOpen,
  setPreferencesOpen,
  setDarkMode,
  darkMode
} = useLayout()

const { isDarkModeActive } = useLayoutTheme()
const authEnabled = appConfig.auth.enabled

const user = computed(() => authStore.user)
const displayName = computed(() => {
  const currentUser = user.value
  if (!currentUser) {
    return brand.name
  }

  const fullName = [currentUser.first_name, currentUser.last_name].filter(Boolean).join(' ').trim()
  return fullName || currentUser.email || currentUser.username || brand.name
})

const shellClasses = computed(() => ({
  'app-shell--fixed': sidebarMode.value === 'fixed',
  'app-shell--overlay': sidebarMode.value === 'overlay',
  'app-shell--collapsed': sidebarCollapsed.value
}))

const pageTransition = computed(() => {
  if (motionPreset.value === 'reduced') {
    return 'page-none'
  }
  return motionPreset.value === 'calm' ? 'page-fade' : 'page-slide'
})

const preferencesVisible = computed({
  get: () => preferencesOpen.value,
  set: (value) => setPreferencesOpen(value)
})

const toggleDarkMode = () => {
  setDarkMode(isDarkModeActive.value ? 'light' : 'dark')
}

const logout = async () => {
  if (!authEnabled) {
    return
  }

  authStore.logout()
  toast.add({
    severity: 'info',
    summary: 'Deconnexion',
    detail: 'A bientot.',
    life: 3000
  })
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="app-shell" :class="shellClasses">
    <AppSidebar
      :brand="brand"
      :menu-model="menuModel"
      :sidebar-mode="sidebarMode"
      :collapsed="sidebarCollapsed"
      :mobile-open="mobileSidebarOpen"
      :user="user"
      :display-name="displayName"
      :show-profile-actions="authEnabled"
      @close-mobile-sidebar="setMobileSidebarOpen(false)"
      @toggle-sidebar="handlePrimaryNavigation"
      @logout="logout"
    />

    <div class="app-shell__main">
      <AppTopbar
        :brand="brand"
        :page-title="pageTitle"
        :page-section="pageSection"
        :user="user"
        :display-name="displayName"
        :search-placeholder="shellConfig.topbarSearchPlaceholder"
        :is-dark-mode-active="isDarkModeActive"
        :dark-mode="darkMode"
        :show-profile-actions="authEnabled"
        @toggle-navigation="handlePrimaryNavigation"
        @open-preferences="setPreferencesOpen(true)"
        @toggle-dark-mode="toggleDarkMode"
        @logout="logout"
      />

      <div class="app-shell__content-wrap">
        <Breadcrumb
          v-if="shellConfig.breadcrumbs"
          :home="homeBreadcrumb"
          :model="breadcrumbs"
          class="app-shell__breadcrumb"
        />

        <main class="app-shell__content">
          <RouterView v-slot="{ Component, route }">
            <Transition :name="pageTransition" mode="out-in">
              <component :is="Component" :key="route.path" />
            </Transition>
          </RouterView>
        </main>
      </div>
    </div>

    <AppThemePanel v-model="preferencesVisible" />
  </div>
</template>
