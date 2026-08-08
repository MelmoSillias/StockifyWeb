<template>
  <section id="fonctionnalites" class="landing-features mkt-section mkt-section--muted">
    <div class="mkt-container">
      <div class="mkt-section-header mkt-section-header--center">
        <p class="mkt-eyebrow">Fonctionnalités</p>
        <h2 class="mkt-title mkt-title--sm">Ce que Lafia Sugu vous permet de faire</h2>
        <p class="mkt-lead">
          Stock, ventes, clients, fournisseurs et rapports réunis dans un outil
          conçu pour le suivi quotidien d'un commerce.
        </p>
      </div>

      <div class="landing-features__grid">
        <article
          v-for="item in featureModules"
          :key="item.title"
          class="landing-features__card mkt-card"
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
</script>

<style scoped>
.landing-features__grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.875rem;
}

.landing-features__card {
  padding: 1.125rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.landing-features__icon {
  width: 2.25rem;
  height: 2.25rem;
  font-size: 1rem;
}

.landing-features__card h3 {
  margin: 0 0 0.35rem;
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.35;
  color: var(--mkt-text);
}

.landing-features__card p {
  color: var(--mkt-text-muted);
  line-height: 1.55;
  font-size: 0.875rem;
  margin: 0;
}

@media (max-width: 960px) {
  .landing-features__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 560px) {
  .landing-features__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 400px) {
  .landing-features__card {
    padding: 1rem;
  }
}
</style>
