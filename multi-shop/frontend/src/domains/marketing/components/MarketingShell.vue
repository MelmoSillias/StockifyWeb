<template>
  <div class="marketing-shell" :class="{ 'marketing-shell--landing': landing }">
    <header
      ref="navRef"
      class="marketing-shell__nav"
      :class="{
        'marketing-shell__nav--open': menuOpen,
        'marketing-shell__nav--landing': landing,
        'marketing-shell__nav--scrolled': scrolled && landing
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
        <RouterLink :to="anchorTo('avantages')" @click="closeMenu">Produit</RouterLink>
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
            <p>Gestion catalogue, stock et commerce pour les commerces exigeants.</p>
          </div>
        </div>
        <div class="marketing-shell__footer-columns">
          <div class="marketing-shell__footer-col">
            <span class="marketing-shell__footer-label">Produit</span>
            <RouterLink :to="anchorTo('avantages')">Avantages</RouterLink>
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
        <span class="marketing-shell__footer-beta">Bêta ouverte — Starter gratuit</span>
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
const scrolled = ref(false)
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

const onScroll = () => {
  scrolled.value = window.scrollY > 48
}

onMounted(async () => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()

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
  window.removeEventListener('scroll', onScroll)
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
  --mkt-nav-gap: 3rem;
}

.marketing-shell--landing .marketing-shell__nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 30;
  background: transparent;
  border-bottom: 1px solid transparent;
  transition: background 280ms ease, border-color 280ms ease, backdrop-filter 280ms ease;
}

.marketing-shell__nav--landing.marketing-shell__nav--scrolled {
  background: color-mix(in srgb, var(--mkt-navy) 82%, transparent);
  backdrop-filter: blur(16px);
  border-bottom-color: rgba(255, 255, 255, 0.06);
}

.marketing-shell__nav {
  position: sticky;
  top: 0;
  z-index: 20;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.1rem clamp(1rem, 3vw, 2rem);
  background: var(--mkt-white);
  border-bottom: 1px solid var(--mkt-border-light);
}

.marketing-shell__nav--landing {
  background: transparent;
  border-bottom: none;
  padding-block: 0.85rem;
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
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 0.75rem;
  background: var(--mkt-accent);
  color: white;
  font-weight: 800;
  font-size: 0.85rem;
  overflow: hidden;
  box-shadow: 0 4px 16px var(--mkt-accent-glow);
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
  border-radius: 0.5rem;
  box-shadow: none;
}

.marketing-shell__nav--landing .marketing-shell__brand-mark--logo {
  width: 4.75rem;
  height: 4.75rem;
}

.marketing-shell__nav:not(.marketing-shell__nav--landing) .marketing-shell__brand-mark--logo {
  box-shadow: var(--mkt-shadow-sm);
}

.marketing-shell__brand-mark--logo img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
  object-position: top center;
  padding: 0;
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
  width: 3.25rem;
  height: 3.25rem;
  max-width: none;
  padding: 0;
  margin: 0;
  background: var(--mkt-logo-bg);
  border: 1px solid var(--mkt-logo-border);
  box-shadow: none;
  aspect-ratio: 1;
}

.marketing-shell__brand-name {
  font-weight: 800;
  font-size: 1.05rem;
  color: var(--mkt-light-text);
  letter-spacing: -0.02em;
}

.marketing-shell__nav--landing .marketing-shell__brand-name {
  color: white;
  font-size: 1.2rem;
}

.marketing-shell__nav--landing .marketing-shell__links a:not(.marketing-shell__cta) {
  color: rgba(255, 255, 255, 0.82);
}

.marketing-shell__menu-toggle {
  display: none;
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid var(--mkt-border-light);
  border-radius: 0.75rem;
  background: transparent;
  cursor: pointer;
}

.marketing-shell__nav--landing .marketing-shell__menu-toggle {
  border-color: rgba(255, 255, 255, 0.2);
  color: white;
}

.marketing-shell__links {
  display: flex;
  align-items: center;
  gap: clamp(0.75rem, 2vw, 1.75rem);
}

.marketing-shell__links a {
  font-size: 0.92rem;
  font-weight: 500;
  color: var(--mkt-light-muted);
  transition: color 160ms ease;
  text-decoration: none;
}

.marketing-shell__links a:hover {
  color: var(--mkt-light-text);
}

.marketing-shell__link-btn {
  font-size: 0.92rem;
  font-weight: 500;
  color: var(--mkt-light-muted);
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  transition: color 160ms ease;
}

.marketing-shell__link-btn:hover {
  color: var(--mkt-light-text);
}

.marketing-shell__nav--landing .marketing-shell__link-btn {
  color: rgba(255, 255, 255, 0.82);
}

.marketing-shell__nav--landing .marketing-shell__link-btn:hover {
  color: white;
}

.marketing-shell__footer-link-btn {
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.92rem;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  text-align: left;
  transition: color 160ms ease;
}

.marketing-shell__footer-link-btn:hover {
  color: white;
}

.marketing-shell__nav--landing .marketing-shell__links a:hover:not(.marketing-shell__cta) {
  color: white;
}

.marketing-shell__cta {
  padding: 0.6rem 1.15rem !important;
  border-radius: var(--mkt-radius-pill) !important;
  background: var(--mkt-accent) !important;
  color: white !important;
  font-weight: 700 !important;
  box-shadow: 0 8px 28px var(--mkt-accent-glow);
  transition: transform 180ms ease, box-shadow 180ms ease !important;
}

.marketing-shell__cta:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 36px var(--mkt-accent-glow) !important;
}

.marketing-shell__main {
  flex: 1;
}

.marketing-shell__footer {
  background: var(--mkt-navy);
  color: white;
  padding: 3rem 0 0;
}

.marketing-shell__footer-inner {
  display: flex;
  justify-content: space-between;
  gap: 2.5rem;
  align-items: flex-start;
  padding-bottom: 2.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.marketing-shell__footer-brand {
  display: flex;
  gap: 0.85rem;
  align-items: flex-start;
  max-width: 24rem;
}

.marketing-shell__footer-brand p {
  margin-top: 0.35rem;
  color: rgba(255, 255, 255, 0.55);
  line-height: 1.65;
  font-size: 0.92rem;
}

.marketing-shell__footer-columns {
  display: flex;
  gap: clamp(2rem, 5vw, 4rem);
}

.marketing-shell__footer-col {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.marketing-shell__footer-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.4);
  margin-bottom: 0.25rem;
}

.marketing-shell__footer-col a {
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.92rem;
  text-decoration: none;
  transition: color 160ms ease;
}

.marketing-shell__footer-col a:hover {
  color: white;
}

.marketing-shell__footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 0;
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.4);
}

.marketing-shell__footer-beta {
  padding: 0.25rem 0.65rem;
  border-radius: var(--mkt-radius-pill);
  background: var(--mkt-accent-soft);
  color: var(--mkt-accent);
  font-weight: 600;
  font-size: 0.75rem;
}

@media (max-width: 860px) {
  .marketing-shell__menu-toggle {
    display: grid;
    place-items: center;
  }

  .marketing-shell__brand-mark--logo {
    width: 2.5rem;
    height: 2.5rem;
  }

  .marketing-shell__nav--landing .marketing-shell__brand-mark--logo {
    width: 3.5rem;
    height: 3.5rem;
  }

  .marketing-shell__nav--landing .marketing-shell__brand-name {
    font-size: 1.05rem;
  }

  .marketing-shell__links {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 1rem;
    right: 1rem;
    flex-direction: column;
    align-items: stretch;
    padding: 1rem;
    border-radius: var(--mkt-radius);
    background: var(--mkt-navy);
    border: 1px solid rgba(255, 255, 255, 0.1);
    opacity: 0;
    pointer-events: none;
    transform: translateY(-8px);
    transition: 180ms ease;
  }

  .marketing-shell__nav:not(.marketing-shell__nav--landing) .marketing-shell__links {
    background: white;
    border-color: var(--mkt-border-light);
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

  .marketing-shell__nav--landing {
    padding-block: 0.65rem;
  }

  .marketing-shell__nav--landing .marketing-shell__brand-mark--logo {
    width: 2.85rem;
    height: 2.85rem;
  }

  .marketing-shell__nav--landing .marketing-shell__brand-name {
    font-size: 0.95rem;
  }

  .marketing-shell__links {
    left: 0.75rem;
    right: 0.75rem;
    padding: 0.85rem;
  }

  .marketing-shell__footer {
    padding-top: 2rem;
  }

  .marketing-shell__footer-inner {
    padding-bottom: 1.5rem;
    gap: 1.25rem;
  }

  .marketing-shell__footer-brand p,
  .marketing-shell__footer-col a {
    font-size: 0.85rem;
  }

  .marketing-shell__footer-bottom {
    padding: 1rem 0;
    font-size: 0.75rem;
  }
}
</style>
