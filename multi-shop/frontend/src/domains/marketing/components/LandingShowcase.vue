<template>
  <section class="landing-showcase mkt-section mkt-section--light">
    <div class="mkt-container landing-showcase__grid">
      <div class="landing-showcase__copy">
        <p class="mkt-eyebrow">{{ section.eyebrow }}</p>
        <h2 class="mkt-title mkt-title--sm">{{ section.title }}</h2>
        <p class="mkt-lead">{{ section.description }}</p>
        <div class="landing-showcase__actions">
          <RouterLink :to="ctaTo" class="mkt-btn mkt-btn--primary">
            {{ ctaLabel(section.cta) }}
          </RouterLink>
        </div>
      </div>

      <div class="landing-showcase__visual">
        <AppPreview :title="section.mockup.title">
          <MockupDashboard v-bind="section.mockup" />
        </AppPreview>
      </div>
    </div>
  </section>
</template>

<script setup>
import AppPreview from '@/domains/marketing/components/AppPreview.vue'
import MockupDashboard from '@/domains/marketing/components/MockupDashboard.vue'
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'

defineProps({
  section: {
    type: Object,
    required: true
  }
})

const { isAuthenticated, signupTo } = useMarketingAuth()

const ctaTo = signupTo()
const ctaLabel = (label) => (isAuthenticated.value ? 'Accéder au tableau de bord' : label)
</script>

<style scoped>
.landing-showcase__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: clamp(2rem, 4vw, 3rem);
  align-items: center;
}

.landing-showcase__copy {
  display: flex;
  flex-direction: column;
}

.landing-showcase__copy .mkt-lead {
  margin-top: 0.75rem;
  margin-bottom: 0;
  max-width: 28rem;
}

.landing-showcase__actions {
  margin-top: 1.5rem;
}

.landing-showcase__visual {
  display: flex;
  justify-content: center;
}

@media (max-width: 900px) {
  .landing-showcase__grid {
    grid-template-columns: 1fr;
  }
}
</style>
