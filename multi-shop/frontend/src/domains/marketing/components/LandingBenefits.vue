<template>
  <section id="avantages" class="landing-benefits mkt-section mkt-section--light">
    <div class="mkt-container--narrow">
      <div class="mkt-section-header mkt-section-header--center mkt-reveal" :ref="register">
        <p class="mkt-eyebrow">Pourquoi StockifyWeb</p>
        <h2 class="mkt-title mkt-title--sm">Trois piliers pour piloter votre activité.</h2>
      </div>
      <div class="landing-benefits__grid">
        <article
          v-for="(item, index) in heroPillars"
          :key="item.title"
          class="landing-benefits__card mkt-glass mkt-reveal"
          :ref="register"
          :style="{ transitionDelay: `${index * 100}ms` }"
        >
          <span class="mkt-icon-wrap">
            <i :class="item.icon"></i>
          </span>
          <h3>{{ item.title }}</h3>
          <p>{{ item.description }}</p>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { heroPillars } from '@/domains/marketing/config/marketingContent'
import { useScrollReveal } from '@/domains/marketing/composables/useScrollReveal'

const { register } = useScrollReveal()
</script>

<style scoped>
.landing-benefits {
  padding-block: clamp(4rem, 7vw, 6rem);
}

.landing-benefits :deep(.mkt-section-header) {
  width: 100%;
  text-align: center;
  align-items: center;
}

.landing-benefits :deep(.mkt-eyebrow) {
  align-self: center;
}

.landing-benefits :deep(.mkt-title) {
  text-align: center;
  max-width: 28rem;
  margin-inline: auto;
}

.landing-benefits__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(1.25rem, 3vw, 1.75rem);
}

.landing-benefits__card {
  padding: clamp(1.75rem, 3vw, 2.25rem);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0;
}

.landing-benefits__card .mkt-icon-wrap {
  margin-bottom: 1.25rem;
}

.landing-benefits__card h3 {
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  line-height: 1.35;
  margin-bottom: 0.65rem;
}

.landing-benefits__card p {
  color: var(--mkt-light-muted);
  line-height: 1.65;
  font-size: 0.925rem;
  margin: 0;
  max-width: 18rem;
}

@media (max-width: 768px) {
  .landing-benefits__grid {
    grid-template-columns: 1fr;
  }
}
</style>
