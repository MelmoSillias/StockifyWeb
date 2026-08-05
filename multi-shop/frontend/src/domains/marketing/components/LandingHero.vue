<template>
  <section class="landing-hero mkt-section mkt-section--dark">
    <div class="mkt-grid-bg" aria-hidden="true"></div>
    <div class="landing-hero__glow landing-hero__glow--1" aria-hidden="true"></div>
    <div class="landing-hero__glow landing-hero__glow--2" aria-hidden="true"></div>

    <div class="mkt-container landing-hero__grid">
      <div class="landing-hero__copy mkt-reveal" :ref="register">
        <span class="mkt-tag">Nouveau · Multi-boutique</span>
        <h1 class="mkt-title">
          Pilotez votre stock et votre commerce
          <span class="mkt-title-accent"> en toute sérénité.</span>
        </h1>
        <p class="mkt-lead">
          LafiaSugu centralise catalogue, inventaire, ventes et finances dans une
          expérience soignée — pensée pour les commerces qui veulent grandir sans
          multiplier les outils.
        </p>
        <div class="landing-hero__actions">
          <RouterLink :to="primaryAction.to" class="mkt-btn mkt-btn--primary">
            {{ isAuthenticated ? 'Accéder au dashboard' : 'Essayer maintenant' }}
            <i class="pi pi-arrow-right"></i>
          </RouterLink>
          <RouterLink :to="anchorTo('tarifs')" class="mkt-btn mkt-btn--ghost-light">
            Voir les tarifs
          </RouterLink>
        </div>
        <div class="landing-hero__stats">
          <div v-for="stat in heroStats" :key="stat.label" class="landing-hero__stat">
            <strong>{{ stat.value }}</strong>
            <span>{{ stat.label }}</span>
          </div>
        </div>
      </div>

      <div class="landing-hero__phones mkt-reveal" :ref="register">
        <AppMockup class="landing-hero__phone landing-hero__phone--back" offset>
          <MockupDashboard
            title="Analytics"
            :stats="[
              { label: 'CA semaine', value: '2.4M' },
              { label: 'Croissance', value: '+18%', hint: 'vs sem. passée' }
            ]"
            :bars="[30, 55, 42, 70, 48, 82, 65]"
          />
        </AppMockup>
        <AppMockup class="landing-hero__phone landing-hero__phone--front">
          <MockupDashboard
            title="Dashboard"
            :stats="[
              { label: 'Stock actif', value: '1 284' },
              { label: 'Ventes jour', value: '48' }
            ]"
            :bars="[42, 68, 54, 82, 61, 74, 90, 58]"
          />
        </AppMockup>
      </div>
    </div>
  </section>
</template>

<script setup>
import AppMockup from '@/domains/marketing/components/AppMockup.vue'
import MockupDashboard from '@/domains/marketing/components/MockupDashboard.vue'
import { heroStats } from '@/domains/marketing/config/marketingContent'
import { useScrollReveal } from '@/domains/marketing/composables/useScrollReveal'
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'

const { register } = useScrollReveal()
const { isAuthenticated, primaryAction, anchorTo } = useMarketingAuth()
</script>

<style scoped>
.landing-hero {
  overflow: hidden;
}

.landing-hero__glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
}

.landing-hero__glow--1 {
  width: 32rem;
  height: 32rem;
  top: -8rem;
  right: -6rem;
  background: var(--mkt-accent-soft);
}

.landing-hero__glow--2 {
  width: 24rem;
  height: 24rem;
  bottom: -4rem;
  left: -4rem;
  background: color-mix(in srgb, var(--mkt-accent-band) 15%, transparent);
}

.landing-hero__grid {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(16rem, 0.85fr);
  gap: clamp(2.5rem, 5vw, 4.5rem);
  align-items: center;
}

.landing-hero__copy {
  display: flex;
  flex-direction: column;
}

.landing-hero__copy .mkt-tag {
  margin-bottom: 1.25rem;
}

.landing-hero__copy .mkt-title {
  margin-bottom: 1.25rem;
}

.landing-hero__copy .mkt-lead {
  margin-top: 0;
  margin-bottom: 2rem;
  max-width: 31rem;
}

.landing-hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.85rem;
  margin-bottom: 2.5rem;
}

.landing-hero__stats {
  display: flex;
  flex-wrap: wrap;
  gap: clamp(2rem, 4vw, 3rem);
  padding-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.landing-hero__stat {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.landing-hero__stat strong {
  font-size: 1.4rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: white;
  line-height: 1.2;
}

.landing-hero__stat span {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.55);
  font-weight: 500;
  line-height: 1.4;
}

.landing-hero__phones {
  position: relative;
  min-height: 24rem;
  display: flex;
  justify-content: center;
  align-items: center;
}

.landing-hero__phone {
  position: absolute;
  animation: hero-float 6s ease-in-out infinite;
}

.landing-hero__phone :deep(.app-mockup__frame) {
  box-shadow:
    var(--mkt-shadow-phone),
    0 0 60px var(--mkt-accent-soft);
}

.landing-hero__phone--back {
  right: 8%;
  top: 0;
  transform: rotate(-8deg) scale(0.92);
  opacity: 0.88;
  z-index: 1;
  animation-delay: -3s;
}

.landing-hero__phone--front {
  left: 8%;
  bottom: 0;
  transform: rotate(4deg);
  z-index: 2;
}

@keyframes hero-float {
  0%, 100% { translate: 0 0; }
  50% { translate: 0 -10px; }
}

@media (max-width: 900px) {
  .landing-hero__grid {
    grid-template-columns: 1fr;
  }

  .landing-hero__phones {
    min-height: 20rem;
    margin-top: 1.5rem;
  }

  .landing-hero__phone--back {
    right: 0;
  }

  .landing-hero__phone--front {
    left: 0;
  }
}
</style>
