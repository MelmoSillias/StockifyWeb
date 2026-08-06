<script setup>
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'

import Button from 'primevue/button'
import Popover from 'primevue/popover'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

import { useBreakpoint } from '@/domains/layout/composables/useBreakpoint'
import { useAuthStore } from '@/domains/auth/stores/auth'
import { useShopStore } from '@/domains/shop/stores/shop'

const shopStore = useShopStore()
const authStore = useAuthStore()
const { activeShopId, accessibleShops, activeShop } = storeToRefs(shopStore)
const { isMobile } = useBreakpoint()

const shopMenu = ref()

const visible = computed(() => {
  const user = authStore.user
  if (!user) {
    return false
  }

  return user.is_platform_owner || accessibleShops.value.length > 0
})

const shopInitials = (name = '') =>
  name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('') || '?'

const statusMeta = (status) => {
  if (status === 'active') {
    return { label: 'Active', severity: 'success' }
  }

  return { label: 'Inactive', severity: 'danger' }
}

const onShopChange = async (shopId) => {
  if (!shopId || shopId === activeShopId.value) {
    return
  }

  await shopStore.switchShop(shopId)
  window.location.reload()
}

const toggleShopMenu = (event) => {
  shopMenu.value?.toggle(event)
}

const selectShopMobile = async (shopId) => {
  shopMenu.value?.hide()
  await onShopChange(shopId)
}
</script>

<template>
  <div v-if="visible" class="shop-selector" :class="{ 'shop-selector--mobile': isMobile }">
    <Select
      v-if="!isMobile"
      :model-value="activeShopId"
      :options="accessibleShops"
      option-label="name"
      option-value="id"
      placeholder="Boutique active"
      :filter="accessibleShops.length > 5"
      filter-placeholder="Rechercher une boutique..."
      class="shop-selector__select"
      @update:model-value="onShopChange"
    >
      <template #value="{ value }">
        <div v-if="value" class="shop-selector__option shop-selector__option--value">
          <span class="shop-selector__avatar" aria-hidden="true">
            {{ shopInitials(activeShop?.name) }}
          </span>
          <span class="shop-selector__copy">
            <span class="shop-selector__name">{{ activeShop?.name }}</span>
            <span class="shop-selector__slug">{{ activeShop?.slug }}</span>
          </span>
        </div>
        <span v-else class="shop-selector__placeholder">Boutique active</span>
      </template>

      <template #option="{ option }">
        <div
          class="shop-selector__option"
          :class="{ 'shop-selector__option--selected': option.id === activeShopId }"
        >
          <span class="shop-selector__avatar" aria-hidden="true">
            {{ shopInitials(option.name) }}
          </span>
          <span class="shop-selector__copy">
            <span class="shop-selector__name">{{ option.name }}</span>
            <span class="shop-selector__slug">{{ option.slug }}</span>
          </span>
          <Tag
            :value="statusMeta(option.status).label"
            :severity="statusMeta(option.status).severity"
            rounded
            class="shop-selector__status"
          />
          <i v-if="option.id === activeShopId" class="pi pi-check shop-selector__check" aria-hidden="true" />
        </div>
      </template>
    </Select>

    <template v-else>
      <Button
        severity="secondary"
        rounded
        text
        class="shop-selector__trigger"
        :aria-label="activeShop ? `Boutique active : ${activeShop.name}` : 'Choisir une boutique'"
        aria-haspopup="true"
        @click="toggleShopMenu"
      >
        <span class="shop-selector__trigger-mark" aria-hidden="true">
          {{ shopInitials(activeShop?.name) }}
        </span>
        <i class="pi pi-chevron-down shop-selector__trigger-chevron" aria-hidden="true" />
      </Button>

      <Popover ref="shopMenu" class="shop-selector__menu">
        <div class="shop-selector__menu-header">
          <p class="shop-selector__menu-title">Boutiques</p>
          <span class="shop-selector__menu-count">{{ accessibleShops.length }}</span>
        </div>

        <ul class="shop-selector__list" role="listbox" aria-label="Boutiques accessibles">
          <li v-for="shop in accessibleShops" :key="shop.id" role="option" :aria-selected="shop.id === activeShopId">
            <button
              type="button"
              class="shop-selector__list-item"
              :class="{ 'shop-selector__list-item--active': shop.id === activeShopId }"
              @click="selectShopMobile(shop.id)"
            >
              <span class="shop-selector__avatar" aria-hidden="true">
                {{ shopInitials(shop.name) }}
              </span>
              <span class="shop-selector__copy">
                <span class="shop-selector__name">{{ shop.name }}</span>
                <span class="shop-selector__slug">{{ shop.slug }}</span>
              </span>
              <Tag
                :value="statusMeta(shop.status).label"
                :severity="statusMeta(shop.status).severity"
                rounded
                class="shop-selector__status"
              />
              <i v-if="shop.id === activeShopId" class="pi pi-check shop-selector__check" aria-hidden="true" />
            </button>
          </li>
        </ul>
      </Popover>
    </template>
  </div>
</template>

<style scoped>
.shop-selector {
  min-width: 12.5rem;
}

.shop-selector--mobile {
  min-width: 0;
}

.shop-selector__select {
  width: 100%;
}

.shop-selector__select :deep(.p-select-label) {
  padding-top: 0.35rem;
  padding-bottom: 0.35rem;
}

.shop-selector__option {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  min-width: 0;
  width: 100%;
}

.shop-selector__option--value {
  padding-block: 0.05rem;
}

.shop-selector__option--selected {
  font-weight: 600;
}

.shop-selector__avatar,
.shop-selector__trigger-mark {
  flex-shrink: 0;
  width: 1.85rem;
  height: 1.85rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: calc(var(--layout-radius-sm, 0.5rem) - 2px);
  background: linear-gradient(135deg, var(--layout-accent, var(--p-primary-500)), var(--layout-accent-strong, var(--p-primary-600)));
  color: white;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}

.shop-selector__copy {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1 1 auto;
  gap: 0.1rem;
}

.shop-selector__name {
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.2;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.shop-selector__slug {
  font-size: 0.72rem;
  color: var(--layout-text-muted);
  line-height: 1.2;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.shop-selector__status {
  flex-shrink: 0;
}

.shop-selector__status :deep(.p-tag-label) {
  font-size: 0.68rem;
}

.shop-selector__check {
  flex-shrink: 0;
  color: var(--layout-accent, var(--p-primary-500));
  font-size: 0.85rem;
}

.shop-selector__placeholder {
  color: var(--layout-text-muted);
}

.shop-selector__trigger {
  gap: 0.15rem;
  padding-inline: 0.35rem;
}

.shop-selector__trigger-mark {
  width: 1.65rem;
  height: 1.65rem;
  font-size: 0.62rem;
}

.shop-selector__trigger-chevron {
  font-size: 0.65rem;
  color: var(--layout-text-muted);
}

.shop-selector__menu {
  min-width: min(18rem, calc(100vw - 2rem));
}

.shop-selector__menu-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.15rem 0.35rem 0.65rem;
  border-bottom: 1px solid var(--layout-panel-border, var(--p-content-border-color));
  margin-bottom: 0.35rem;
}

.shop-selector__menu-title {
  margin: 0;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--layout-text-muted);
}

.shop-selector__menu-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.35rem;
  height: 1.35rem;
  padding-inline: 0.35rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--layout-accent, var(--p-primary-500)) 14%, transparent);
  color: var(--layout-accent, var(--p-primary-500));
  font-size: 0.72rem;
  font-weight: 700;
}

.shop-selector__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  max-height: min(18rem, 55vh);
  overflow-y: auto;
}

.shop-selector__list-item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  width: 100%;
  padding: 0.55rem 0.6rem;
  border: 1px solid transparent;
  border-radius: calc(var(--layout-radius-sm, 0.5rem));
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
  transition:
    background var(--layout-transition-fast, 150ms ease),
    border-color var(--layout-transition-fast, 150ms ease);
}

.shop-selector__list-item:hover,
.shop-selector__list-item:focus-visible {
  background: color-mix(in srgb, var(--layout-panel-bg, var(--p-content-background)) 88%, var(--layout-accent, var(--p-primary-500)) 12%);
  border-color: color-mix(in srgb, var(--layout-panel-border, var(--p-content-border-color)) 70%, var(--layout-accent, var(--p-primary-500)) 30%);
  outline: none;
}

.shop-selector__list-item--active {
  background: color-mix(in srgb, var(--layout-accent, var(--p-primary-500)) 10%, transparent);
  border-color: color-mix(in srgb, var(--layout-accent, var(--p-primary-500)) 24%, transparent);
}
</style>
