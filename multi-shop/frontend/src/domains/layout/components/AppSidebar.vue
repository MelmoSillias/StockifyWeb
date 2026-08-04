<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'

import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Drawer from 'primevue/drawer'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Popover from 'primevue/popover'

import AppNavItem from './AppNavItem.vue'
import AppNavGroup from './AppNavGroup.vue'
import AppSecondarySidebar from './AppSecondarySidebar.vue'

import { useLayoutStore } from '@/domains/layout/stores/layout'

const props = defineProps({
  brand: {
    type: Object,
    required: true
  },
  menuModel: {
    type: Array,
    required: true
  },
  sidebarMode: {
    type: String,
    required: true
  },
  collapsed: {
    type: Boolean,
    required: true
  },
  mobileOpen: {
    type: Boolean,
    required: true
  },
  user: {
    type: Object,
    default: null
  },
  displayName: {
    type: String,
    default: 'User'
  },
  showProfileActions: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close-mobile-sidebar', 'toggle-sidebar', 'logout'])

const route = useRoute()
const router = useRouter()
const layoutStore = useLayoutStore()
const { sidebarLogoPosition, sidebarProfilePosition, sidebarSearchPosition } = storeToRefs(layoutStore)

const secondaryGroup = ref(null)
const profileMenu = ref()

const drawerVisible = computed({
  get: () => props.mobileOpen,
  set: (value) => {
    if (!value) {
      emit('close-mobile-sidebar')
    }
  }
})

const userInitials = computed(() => {
  return props.displayName
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((value) => value[0]?.toUpperCase())
    .join('')
})

const activeKey = computed(() => {
  const routeName = route.name

  // Check direct match
  for (const item of props.menuModel) {
    if (item.routeName === routeName) {
      return item.key
    }

    // Check children
    if (item.items) {
      for (const child of item.items) {
        if (child.routeName === routeName) {
          return child.key
        }
      }
    }
  }

  return null
})

const activeParentGroup = computed(() => {
  const routeName = route.name

  for (const item of props.menuModel) {
    if (item.items) {
      for (const child of item.items) {
        if (child.routeName === routeName) {
          return item
        }
      }
    }
  }

  return null
})

const effectiveSecondaryGroup = computed(() => {
  if (props.collapsed) {
    return secondaryGroup.value || activeParentGroup.value
  }
  return null
})

const showSearch = computed(() => sidebarSearchPosition.value !== 'hidden')
const searchBeforeMenu = computed(() => sidebarSearchPosition.value === 'before-menu')
const showProfile = computed(() => props.showProfileActions && sidebarProfilePosition.value !== 'hidden')
const profileAtTop = computed(() => sidebarProfilePosition.value === 'top')
const logoAtTop = computed(() => sidebarLogoPosition.value === 'top')
const fallbackEmail = computed(() => props.user?.email || props.brand.supportEmail || props.brand.tagline)

const handleParentClick = (group) => {
  if (props.collapsed) {
    // Toggle: ferme si on reclique sur le même groupe
    if (secondaryGroup.value && secondaryGroup.value.key === group.key) {
      secondaryGroup.value = null
    } else {
      secondaryGroup.value = group
    }
  }
}

const closeSecondarySidebar = () => {
  secondaryGroup.value = null
}

const toggleProfileMenu = (event) => {
  profileMenu.value.toggle(event)
}

const handleSearchClick = () => {
  if (props.collapsed) {
    emit('toggle-sidebar')
  }
}

const handleLogout = () => {
  profileMenu.value.hide()
  emit('logout')
}

const openProfile = () => {
  profileMenu.value.hide()
  router.push({ name: 'profile' })
}
</script>

<template>
  <div class="app-sidebar-host">
    <div v-if="sidebarMode === 'fixed'" class="app-sidebar-wrapper">
      <aside class="app-sidebar" :class="{ 'app-sidebar--collapsed': collapsed }">
      <div class="app-sidebar__container">
        <!-- Logo Section -->
        <div v-if="logoAtTop" class="app-sidebar__logo">
          <div class="app-sidebar__logo-mark">
            <img v-if="brand.logoUrl" :src="brand.logoUrl" :alt="brand.name" class="app-sidebar__logo-image" />
            <span v-else class="app-sidebar__logo-icon">{{ brand.shortName[0] }}</span>
          </div>
          <div v-if="!collapsed" class="app-sidebar__logo-text">
            <h2 class="app-sidebar__brand">{{ brand.name }}</h2>
            <p class="app-sidebar__tagline">{{ brand.tagline }}</p>
          </div>
        </div>

        <!-- Profile at Top -->
        <div v-if="showProfile && profileAtTop" class="app-sidebar__profile" :class="{ 'app-sidebar__profile--collapsed': collapsed }">
          <Avatar
            :image="user?.avatar"
            :label="userInitials"
            shape="circle"
            size="large"
            style="cursor: pointer"
            @click="toggleProfileMenu"
          />
          <div v-if="!collapsed" class="app-sidebar__profile-info" @click="toggleProfileMenu" style="cursor: pointer">
            <p class="app-sidebar__profile-name">{{ displayName }}</p>
            <span class="app-sidebar__profile-email">{{ fallbackEmail }}</span>
          </div>
        </div>

        <!-- Search Before Menu -->
        <div v-if="showSearch && searchBeforeMenu" class="app-sidebar__search">
          <IconField v-if="!collapsed">
            <InputIcon class="pi pi-search" />
            <InputText placeholder="Rechercher..." fluid />
          </IconField>
          <Button v-else icon="pi pi-search" severity="secondary" rounded text @click="handleSearchClick" />
        </div>

        <!-- Navigation Menu -->
        <nav class="app-sidebar__nav">
          <template v-for="item in menuModel">
            <AppNavItem
              v-if="!item.items || item.items.length === 0"
              :key="`${item.key}-item`"
              :item="item"
              :active="activeKey === item.key"
              :collapsed="collapsed"
            />
            <AppNavGroup
              v-else
              :key="`${item.key}-group`"
              :group="item"
              :collapsed="collapsed"
              :active-key="activeKey"
              @parent-click="handleParentClick"
            />
          </template>
        </nav>

        <!-- Search After Menu -->
        <div v-if="showSearch && !searchBeforeMenu" class="app-sidebar__search app-sidebar__search--after">
          <IconField v-if="!collapsed">
            <InputIcon class="pi pi-search" />
            <InputText placeholder="Rechercher..." fluid />
          </IconField>
          <Button v-else icon="pi pi-search" severity="secondary" rounded text @click="handleSearchClick" />
        </div>

        <!-- Profile at Bottom -->
        <div v-if="showProfile && !profileAtTop" class="app-sidebar__profile app-sidebar__profile--bottom" :class="{ 'app-sidebar__profile--collapsed': collapsed }">
          <Avatar
            :image="user?.avatar"
            :label="userInitials"
            shape="circle"
            :size="collapsed ? 'normal' : 'large'"
            style="cursor: pointer"
            @click="toggleProfileMenu"
          />
          <div v-if="!collapsed" class="app-sidebar__profile-info" @click="toggleProfileMenu" style="cursor: pointer">
            <p class="app-sidebar__profile-name">{{ displayName }}</p>
            <span class="app-sidebar__profile-email">{{ fallbackEmail }}</span>
          </div>
        </div>

        <!-- Logo Center -->
        <div v-if="!logoAtTop" class="app-sidebar__logo app-sidebar__logo--center">
          <div class="app-sidebar__logo-mark">
            <img v-if="brand.logoUrl" :src="brand.logoUrl" :alt="brand.name" class="app-sidebar__logo-image" />
            <span v-else class="app-sidebar__logo-icon">{{ brand.shortName[0] }}</span>
          </div>
          <div v-if="!collapsed" class="app-sidebar__logo-text">
            <h2 class="app-sidebar__brand">{{ brand.name }}</h2>
          </div>
        </div>
      </div>
      </aside>

      <AppSecondarySidebar
        :group="effectiveSecondaryGroup"
        :visible="!!effectiveSecondaryGroup && collapsed"
        :active-key="activeKey"
        @close="closeSecondarySidebar"
      />
    </div>

    <Drawer
      v-model:visible="drawerVisible"
      position="left"
      class="app-sidebar-drawer"
      closeIcon="pi pi-arrow-left"
      :closeButtonProps="{ severity: 'secondary', text: true, rounded: true, 'aria-label': 'Fermer la navigation' }"
    >
      <template #header>
        <div class="app-sidebar__logo">
          <div class="app-sidebar__logo-mark">
            <img v-if="brand.logoUrl" :src="brand.logoUrl" :alt="brand.name" class="app-sidebar__logo-image" />
            <span v-else class="app-sidebar__logo-icon">{{ brand.shortName[0] }}</span>
          </div>
          <div class="app-sidebar__logo-text">
            <h2 class="app-sidebar__brand">{{ brand.name }}</h2>
            <p class="app-sidebar__tagline">{{ brand.tagline }}</p>
          </div>
        </div>
      </template>

      <div class="app-sidebar__drawer-content">
        <div v-if="showProfile && profileAtTop" class="app-sidebar__profile">
          <Avatar
            :image="user?.avatar"
            :label="userInitials"
            shape="circle"
            size="large"
            style="cursor: pointer"
            @click="toggleProfileMenu"
          />
          <div class="app-sidebar__profile-info" @click="toggleProfileMenu" style="cursor: pointer">
            <p class="app-sidebar__profile-name">{{ displayName }}</p>
            <span class="app-sidebar__profile-email">{{ fallbackEmail }}</span>
          </div>
        </div>

        <div v-if="showSearch && searchBeforeMenu" class="app-sidebar__search">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText placeholder="Rechercher..." fluid />
          </IconField>
        </div>

        <nav class="app-sidebar__nav">
          <template v-for="item in menuModel">
          <AppNavItem
            v-if="!item.items || item.items.length === 0"
              :key="`${item.key}-item`"
            :item="item"
            :active="activeKey === item.key"
            :collapsed="false"
          />
          <AppNavGroup
            v-else
              :key="`${item.key}-group`"
            :group="item"
            :collapsed="false"
            :active-key="activeKey"
          />
        </template>
        </nav>

        <div v-if="showSearch && !searchBeforeMenu" class="app-sidebar__search app-sidebar__search--after">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText placeholder="Rechercher..." fluid />
          </IconField>
        </div>

        <div v-if="showProfile && !profileAtTop" class="app-sidebar__profile app-sidebar__profile--bottom">
          <Avatar
            :image="user?.avatar"
            :label="userInitials"
            shape="circle"
            size="large"
            style="cursor: pointer"
            @click="toggleProfileMenu"
          />
          <div class="app-sidebar__profile-info" @click="toggleProfileMenu" style="cursor: pointer">
            <p class="app-sidebar__profile-name">{{ displayName }}</p>
            <span class="app-sidebar__profile-email">{{ fallbackEmail }}</span>
          </div>
        </div>
      </div>
    </Drawer>

    <Popover v-if="showProfile" ref="profileMenu" class="app-profile-menu">
      <div class="app-profile-menu__content">
        <Button
          label="Mon profil"
          icon="pi pi-user"
          text
          fluid
          @click="openProfile"
        />
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
