<template>
  <div class="marketing-shell" :class="{ 'marketing-shell--landing': landing }">
    <header
      class="marketing-shell__nav"
      :class="{
        'marketing-shell__nav--open': menuOpen,
        'marketing-shell__nav--landing': landing,
        'marketing-shell__nav--scrolled': scrolled && landing
      }"
    >
      <RouterLink :to="{ name: 'landing' }" class="marketing-shell__brand">
        <span class="marketing-shell__brand-mark">
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
        <a href="#avantages" @click="closeMenu">Produit</a>
        <a href="#fonctionnalites" @click="closeMenu">Fonctionnalités</a>
        <a href="#tarifs" @click="closeMenu">Tarifs</a>
        <RouterLink :to="{ name: 'login' }" @click="closeMenu">Connexion</RouterLink>
        <RouterLink :to="{ name: 'register' }" class="marketing-shell__cta" @click="closeMenu">
          Créer un compte
        </RouterLink>
      </nav>
    </header>

    <main class="marketing-shell__main">
      <slot />
    </main>

    <footer class="marketing-shell__footer">
      <div class="mkt-container marketing-shell__footer-inner">
        <div class="marketing-shell__footer-brand">
          <span class="marketing-shell__brand-mark marketing-shell__brand-mark--sm">
            {{ brand.shortName }}
          </span>
          <div>
            <strong>{{ brand.name }}</strong>
            <p>Gestion catalogue, stock et commerce pour les commerces exigeants.</p>
          </div>
        </div>
        <div class="marketing-shell__footer-columns">
          <div class="marketing-shell__footer-col">
            <span class="marketing-shell__footer-label">Produit</span>
            <a href="#avantages">Avantages</a>
            <a href="#fonctionnalites">Fonctionnalités</a>
            <a href="#tarifs">Tarifs</a>
          </div>
          <div class="marketing-shell__footer-col">
            <span class="marketing-shell__footer-label">Compte</span>
            <RouterLink :to="{ name: 'register' }">Créer un compte</RouterLink>
            <RouterLink :to="{ name: 'login' }">Connexion</RouterLink>
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
import { onMounted, onUnmounted, ref } from 'vue'
import { appConfig } from '@/config/app'

defineProps({
  landing: {
    type: Boolean,
    default: false
  }
})

const brand = appConfig.branding
const menuOpen = ref(false)
const scrolled = ref(false)
const currentYear = new Date().getFullYear()

const closeMenu = () => {
  menuOpen.value = false
}

const onScroll = () => {
  scrolled.value = window.scrollY > 48
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
})
</script>

<style scoped>
.marketing-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
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
  background: rgba(18, 19, 31, 0.82);
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
}

.marketing-shell__brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  text-decoration: none;
}

.marketing-shell__brand-mark {
  width: 2.4rem;
  height: 2.4rem;
  display: grid;
  place-items: center;
  border-radius: 0.75rem;
  background: var(--mkt-accent);
  color: white;
  font-weight: 800;
  font-size: 0.85rem;
  overflow: hidden;
  box-shadow: 0 4px 16px var(--mkt-accent-glow);
}

.marketing-shell__brand-mark img {
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

.marketing-shell__brand-name {
  font-weight: 800;
  font-size: 1.05rem;
  color: var(--mkt-light-text);
  letter-spacing: -0.02em;
}

.marketing-shell__nav--landing .marketing-shell__brand-name {
  color: white;
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
  box-shadow: 0 12px 36px rgba(16, 185, 129, 0.45) !important;
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
  background: rgba(16, 185, 129, 0.15);
  color: var(--mkt-accent);
  font-weight: 600;
  font-size: 0.75rem;
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
</style>
