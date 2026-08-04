<template>
  <section
    class="landing-showcase mkt-section"
    :class="section.variant === 'accent' ? 'mkt-section--accent' : 'mkt-section--light'"
  >
    <div class="mkt-container landing-showcase__grid" :class="{ 'landing-showcase__grid--reversed': section.reversed }">
      <div class="landing-showcase__copy mkt-reveal" :ref="register">
        <p class="mkt-eyebrow">{{ section.eyebrow }}</p>
        <h2 class="mkt-title mkt-title--sm">{{ section.title }}</h2>
        <p class="mkt-lead">{{ section.description }}</p>
        <div class="landing-showcase__actions">
          <RouterLink
            :to="{ name: 'register' }"
            :class="section.variant === 'accent' ? 'mkt-btn mkt-btn--white' : 'mkt-btn mkt-btn--primary'"
          >
            {{ section.cta }}
          </RouterLink>
          <a href="#tarifs" class="mkt-link">
            En savoir plus
            <i class="pi pi-arrow-right"></i>
          </a>
        </div>
      </div>

      <div class="landing-showcase__visual mkt-reveal" :ref="register">
        <div class="landing-showcase__phones">
          <AppMockup
            :variant="section.variant === 'accent' ? 'default' : 'light'"
            class="landing-showcase__phone landing-showcase__phone--secondary"
            offset
          >
            <MockupDashboard
              v-bind="section.mockup"
              :theme="section.mockup.theme"
            />
          </AppMockup>
          <AppMockup
            :variant="section.variant === 'accent' ? 'default' : 'light'"
            class="landing-showcase__phone landing-showcase__phone--primary"
          >
            <MockupDashboard
              v-bind="section.mockup"
              :theme="section.mockup.theme"
            />
          </AppMockup>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import AppMockup from '@/domains/marketing/components/AppMockup.vue'
import MockupDashboard from '@/domains/marketing/components/MockupDashboard.vue'
import { useScrollReveal } from '@/domains/marketing/composables/useScrollReveal'

defineProps({
  section: {
    type: Object,
    required: true
  }
})

const { register } = useScrollReveal()
</script>

<style scoped>
.landing-showcase__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(16rem, 0.95fr);
  gap: clamp(2rem, 5vw, 4rem);
  align-items: center;
}

.landing-showcase__grid--reversed .landing-showcase__copy {
  order: 2;
}

.landing-showcase__grid--reversed .landing-showcase__visual {
  order: 1;
}

.landing-showcase__copy {
  display: flex;
  flex-direction: column;
}

.landing-showcase__copy .mkt-eyebrow {
  margin-bottom: 0.75rem;
}

.landing-showcase__copy .mkt-title {
  margin-bottom: 1rem;
}

.landing-showcase__copy .mkt-lead {
  margin-top: 0;
  margin-bottom: 0.5rem;
  max-width: 30rem;
}

.landing-showcase__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1.25rem;
  margin-top: 1.75rem;
}

.landing-showcase__phones {
  position: relative;
  min-height: 20rem;
  display: flex;
  justify-content: center;
}

.landing-showcase__phone {
  position: absolute;
}

.landing-showcase__phone--secondary {
  right: 5%;
  top: 0;
  transform: rotate(-6deg) scale(0.9);
  opacity: 0.8;
}

.landing-showcase__phone--primary {
  left: 5%;
  bottom: 0;
  transform: rotate(3deg);
  z-index: 2;
}

@media (max-width: 900px) {
  .landing-showcase__grid,
  .landing-showcase__grid--reversed {
    grid-template-columns: 1fr;
  }

  .landing-showcase__grid--reversed .landing-showcase__copy,
  .landing-showcase__grid--reversed .landing-showcase__visual {
    order: unset;
  }
}
</style>
