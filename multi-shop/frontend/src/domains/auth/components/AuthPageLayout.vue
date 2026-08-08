<template>
  <div class="mkt-page auth-page">
    <MarketingShell>
      <section class="auth-page__section">
        <div class="auth-page__container">
          <RouterLink :to="landingTo" class="auth-page__brand">
            <span
              class="auth-page__brand-mark"
              :class="{ 'auth-page__brand-mark--logo': brand.logoUrl }"
            >
              <img v-if="brand.logoUrl" :src="brand.logoUrl" :alt="brand.name" />
              <span v-else>{{ brand.shortName }}</span>
            </span>
            <span class="auth-page__brand-name">{{ brand.name }}</span>
          </RouterLink>

          <div v-if="title || subtitle" class="auth-page__header">
            <h1 v-if="title">{{ title }}</h1>
            <p v-if="subtitle">{{ subtitle }}</p>
          </div>

          <div class="auth-page__content" :class="{ 'auth-page__content--wide': wide }">
            <slot />
          </div>
        </div>
      </section>
    </MarketingShell>
  </div>
</template>

<script setup>
import { appConfig } from '@/config/app'
import MarketingShell from '@/domains/marketing/components/MarketingShell.vue'
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'

defineProps({
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  wide: {
    type: Boolean,
    default: false
  }
})

const brand = appConfig.branding
const { landingTo } = useMarketingAuth()
</script>

<style>
@import '@/domains/marketing/assets/marketing.css';
@import '@/domains/auth/assets/auth-light-surface.css';
</style>

<style scoped>
.auth-page__section {
  min-height: calc(100vh - var(--mkt-nav-height, 4.5rem));
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 2.5rem 1rem 3rem;
  background: var(--mkt-surface-muted);
}

.auth-page__container {
  width: min(100%, 26rem);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.auth-page__container:has(.auth-page__content--wide) {
  width: min(100%, 32rem);
}

.auth-page__brand {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.65rem;
  text-decoration: none;
}

.auth-page__brand-mark {
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

.auth-page__brand-mark--logo {
  width: 2.75rem;
  height: 2.75rem;
  background: var(--mkt-logo-bg);
  border: 1px solid var(--mkt-logo-border);
}

.auth-page__brand-mark img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.auth-page__brand-name {
  font-weight: 600;
  font-size: 1.05rem;
  color: var(--mkt-text);
}

.auth-page__header {
  text-align: center;
}

.auth-page__header h1 {
  margin: 0;
  font-size: 1.375rem;
  font-weight: 600;
  color: var(--mkt-text);
}

.auth-page__header p {
  margin: 0.35rem 0 0;
  font-size: 0.9375rem;
  color: var(--mkt-text-muted);
  line-height: 1.55;
}

.auth-page__content {
  width: 100%;
}

@media (max-width: 400px) {
  .auth-page__section {
    padding: 1.5rem 1rem 2rem;
  }

  .auth-page__header h1 {
    font-size: 1.2rem;
  }

  .auth-page__header p {
    font-size: 0.875rem;
  }
}
</style>
