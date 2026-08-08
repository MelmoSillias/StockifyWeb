<template>
  <div class="marketing-shell" :class="{ 'marketing-shell--landing': landing }">
    <header
      ref="navRef"
      class="marketing-shell__nav"
      :class="{
        'marketing-shell__nav--open': menuOpen,
        'marketing-shell__nav--landing': landing
      }"
    >
      <RouterLink :to="{ name: 'landing' }" class="marketing-shell__brand">
        <span
          class="marketing-shell__brand-mark"
          :class="{ 'marketing-shell__brand-mark--logo': brand.logoUrl }"
        >
          <img v-if="brand.logoUrl" :src="brand.logoUrl" :alt="brand.name" />
          <span v-else>{{ brand.shortName }}</span>
        </span>
        <span class="marketing-shell__brand-name">{{ brand.name }}</span>
      </RouterLink>

      <button
        type="button"
        class="marketing-shell__menu-toggle"
        aria-label="Menu"
        @click="menuOpen = !menuOpen"
      >
        <i :class="menuOpen ? 'pi pi-times' : 'pi pi-bars'"></i>
      </button>

      <nav class="marketing-shell__links">
        <RouterLink :to="anchorTo('fonctionnalites')" @click="closeMenu">Fonctionnalités</RouterLink>
        <RouterLink :to="anchorTo('tarifs')" @click="closeMenu">Tarifs</RouterLink>
        <RouterLink v-if="!isAuthenticated" :to="loginTo" @click="closeMenu">Connexion</RouterLink>
        <button
          v-else
          type="button"
          class="marketing-shell__link-btn"
          @click="handleLogout"
        >
          Déconnexion
        </button>
        <RouterLink :to="primaryAction.to" class="marketing-shell__cta" @click="closeMenu">
          {{ primaryAction.label }}
        </RouterLink>
      </nav>
    </header>

    <main class="marketing-shell__main">
      <slot />
    </main>

    <footer class="marketing-shell__footer">
      <div class="mkt-container marketing-shell__footer-inner">
        <div class="marketing-shell__footer-brand">
          <span
            class="marketing-shell__brand-mark marketing-shell__brand-mark--sm"
            :class="{ 'marketing-shell__brand-mark--logo': brand.logoUrl }"
          >
            <img v-if="brand.logoUrl" :src="brand.logoUrl" :alt="brand.name" />
            <span v-else>{{ brand.shortName }}</span>
          </span>
          <div>
            <strong>{{ brand.name }}</strong>
            <p>Gestion de stock, ventes et commerce pour les commerces au Mali.</p>
          </div>
        </div>
        <div class="marketing-shell__footer-columns">
          <div class="marketing-shell__footer-col">
            <span class="marketing-shell__footer-label">Produit</span>
            <RouterLink :to="anchorTo('fonctionnalites')">Fonctionnalités</RouterLink>
            <RouterLink :to="anchorTo('tarifs')">Tarifs</RouterLink>
          </div>
          <div class="marketing-shell__footer-col">
            <span class="marketing-shell__footer-label">Compte</span>
            <RouterLink :to="primaryAction.to">{{ primaryAction.label }}</RouterLink>
            <RouterLink v-if="!isAuthenticated" :to="loginTo">Connexion</RouterLink>
            <button v-else type="button" class="marketing-shell__footer-link-btn" @click="handleLogout">
              Déconnexion
            </button>
          </div>
        </div>
      </div>
      <div class="mkt-container marketing-shell__footer-bottom">
        <span>&copy; {{ currentYear }} {{ brand.name }}</span>
        <span>Plan Starter — 1 mois d'essai gratuit</span>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { appConfig } from '@/config/app'
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'

const { isAuthenticated, loginTo, primaryAction, logout, anchorTo } = useMarketingAuth()

const props = defineProps({
  landing: {
    type: Boolean,
    default: false
  }
})

const brand = appConfig.branding
const navRef = ref(null)
const menuOpen = ref(false)
const currentYear = new Date().getFullYear()

let navResizeObserver = null

const syncNavHeight = () => {
  if (!navRef.value) {
    return
  }

  const height = Math.ceil(navRef.value.getBoundingClientRect().height)
  document.documentElement.style.setProperty('--mkt-nav-height', `${height}px`)
}

const closeMenu = () => {
  menuOpen.value = false
  if (props.landing) {
    requestAnimationFrame(syncNavHeight)
  }
}

const handleLogout = async () => {
  closeMenu()
  await logout()
}

onMounted(async () => {
  if (props.landing && navRef.value) {
    await nextTick()
    syncNavHeight()
    navResizeObserver = new ResizeObserver(syncNavHeight)
    navResizeObserver.observe(navRef.value)
    window.addEventListener('resize', syncNavHeight, { passive: true })
  }
})

watch(menuOpen, () => {
  if (props.landing) {
    requestAnimationFrame(syncNavHeight)
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', syncNavHeight)
  navResizeObserver?.disconnect()
  document.documentElement.style.removeProperty('--mkt-nav-height')
})
</script>

<style scoped>
.marketing-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.marketing-shell--landing {
  --mkt-nav-gap: 2rem;
}

.marketing-shell__nav {
  position: sticky;
  top: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.85rem clamp(1rem, 3vw, 2rem);
  background: var(--mkt-surface);
  border-bottom: 1px solid var(--mkt-border);
}

.marketing-shell__nav--landing {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
}

.marketing-shell__brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex: 0 0 auto;
  min-width: 0;
  text-decoration: none;
}

.marketing-shell__brand-mark {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: var(--mkt-radius-sm);
  background: var(--mkt-primary);
  color: white;
  font-weight: 700;
  font-size: 0.8rem;
  overflow: hidden;
}

.marketing-shell__brand-mark--logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.75rem;
  height: 2.75rem;
  padding: 0;
  margin: 0;
  background: var(--mkt-logo-bg);
  border: 1px solid var(--mkt-logo-border);
}

.marketing-shell__brand-mark--logo img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.marketing-shell__brand-mark:not(.marketing-shell__brand-mark--logo) img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 0.25rem;
}

.marketing-shell__brand-mark--sm {
  width: 2rem;
  height: 2rem;
  font-size: 0.75rem;
}

.marketing-shell__brand-mark--sm.marketing-shell__brand-mark--logo {
  width: 2.5rem;
  height: 2.5rem;
}

.marketing-shell__brand-name {
  font-weight: 600;
  font-size: 1rem;
  color: var(--mkt-text);
}

.marketing-shell__menu-toggle {
  display: none;
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid var(--mkt-border);
  border-radius: var(--mkt-radius-sm);
  background: transparent;
  color: var(--mkt-text);
  cursor: pointer;
}

.marketing-shell__links {
  display: flex;
  align-items: center;
  gap: clamp(0.75rem, 2vw, 1.5rem);
}

.marketing-shell__links a {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--mkt-text-muted);
  transition: color 160ms ease;
  text-decoration: none;
}

.marketing-shell__links a:hover {
  color: var(--mkt-text);
}

.marketing-shell__link-btn,
.marketing-shell__footer-link-btn {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--mkt-text-muted);
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  transition: color 160ms ease;
}

.marketing-shell__link-btn:hover,
.marketing-shell__footer-link-btn:hover {
  color: var(--mkt-text);
}

.marketing-shell__footer-link-btn {
  text-align: left;
}

.marketing-shell__cta {
  padding: 0.5rem 1rem !important;
  border-radius: var(--mkt-radius) !important;
  background: var(--mkt-primary) !important;
  color: white !important;
  font-weight: 600 !important;
  font-size: 0.9375rem !important;
}

.marketing-shell__cta:hover {
  background: var(--mkt-primary-strong) !important;
  color: white !important;
}

.marketing-shell__main {
  flex: 1;
}

.marketing-shell__footer {
  background: var(--mkt-surface-muted);
  color: var(--mkt-text);
  padding: 2.5rem 0 0;
  border-top: 1px solid var(--mkt-border);
}

.marketing-shell__footer-inner {
  display: flex;
  justify-content: space-between;
  gap: 2rem;
  align-items: flex-start;
  padding-bottom: 2rem;
  border-bottom: 1px solid var(--mkt-border);
}

.marketing-shell__footer-brand {
  display: flex;
  gap: 0.85rem;
  align-items: flex-start;
  max-width: 24rem;
}

.marketing-shell__footer-brand strong {
  font-weight: 600;
}

.marketing-shell__footer-brand p {
  margin-top: 0.35rem;
  color: var(--mkt-text-muted);
  line-height: 1.6;
  font-size: 0.9375rem;
}

.marketing-shell__footer-columns {
  display: flex;
  gap: clamp(2rem, 5vw, 3.5rem);
}

.marketing-shell__footer-col {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.marketing-shell__footer-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--mkt-text-muted);
  margin-bottom: 0.25rem;
}

.marketing-shell__footer-col a {
  color: var(--mkt-text);
  font-size: 0.9375rem;
  text-decoration: none;
  transition: color 160ms ease;
}

.marketing-shell__footer-col a:hover {
  color: var(--mkt-primary);
}

.marketing-shell__footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 1rem 0;
  font-size: 0.8125rem;
  color: var(--mkt-text-muted);
}

@media (max-width: 860px) {
  .marketing-shell__menu-toggle {
    display: grid;
    place-items: center;
  }

  .marketing-shell__links {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 1rem;
    right: 1rem;
    flex-direction: column;
    align-items: stretch;
    padding: 0.85rem;
    border-radius: var(--mkt-radius);
    background: var(--mkt-surface);
    border: 1px solid var(--mkt-border);
    box-shadow: var(--mkt-shadow-md);
    opacity: 0;
    pointer-events: none;
    transform: translateY(-4px);
    transition: 160ms ease;
  }

  .marketing-shell__nav--open .marketing-shell__links {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
  }

  .marketing-shell__footer-inner {
    flex-direction: column;
  }

  .marketing-shell__footer-bottom {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 400px) {
  .marketing-shell__nav {
    padding-inline: 0.85rem;
  }

  .marketing-shell__links {
    left: 0.75rem;
    right: 0.75rem;
  }

  .marketing-shell__footer {
    padding-top: 2rem;
  }
}
</style>
