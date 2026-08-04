<template>
  <section id="fonctionnalites" class="landing-features mkt-section mkt-section--muted">
    <div class="mkt-container">
      <div class="mkt-section-header mkt-reveal" :ref="register">
        <p class="mkt-eyebrow">Fonctionnalités</p>
        <h2 class="mkt-title mkt-title--sm">Tout ce qu'il faut pour piloter votre activité.</h2>
        <p class="mkt-lead">
          Des modules métier complets, cohérents entre eux, pour couvrir l'ensemble de vos
          opérations quotidiennes sans multiplier les outils.
        </p>
      </div>

      <div class="landing-features__grid">
        <article
          v-for="(item, index) in featureModules"
          :key="item.title"
          class="landing-features__card mkt-glass mkt-reveal"
          :class="`landing-features__card--${item.span}`"
          :ref="register"
          :style="{ transitionDelay: `${index * 70}ms` }"
        >
          <span class="mkt-icon-wrap landing-features__icon">
            <i :class="item.icon"></i>
          </span>
          <div>
            <h3>{{ item.title }}</h3>
            <p>{{ item.description }}</p>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { featureModules } from '@/domains/marketing/config/marketingContent'
import { useScrollReveal } from '@/domains/marketing/composables/useScrollReveal'

const { register } = useScrollReveal()
</script>

<style scoped>
.landing-features__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.landing-features__card {
  padding: 1.35rem;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.landing-features__card--wide {
  grid-column: span 2;
}

.landing-features__icon {
  width: 2.75rem;
  height: 2.75rem;
  font-size: 1.1rem;
}

.landing-features__card h3 {
  margin: 0 0 0.5rem;
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  line-height: 1.35;
}

.landing-features__card p {
  color: var(--mkt-light-muted);
  line-height: 1.65;
  font-size: 0.925rem;
  margin: 0;
}

@media (max-width: 960px) {
  .landing-features__grid {
    grid-template-columns: 1fr;
  }

  .landing-features__card--wide {
    grid-column: auto;
  }
}
</style>
