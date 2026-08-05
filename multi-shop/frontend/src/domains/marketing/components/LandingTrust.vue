<template>
  <section class="landing-trust mkt-section mkt-section--light">
    <div class="mkt-container">
      <p class="landing-trust__label mkt-reveal" :ref="register">
        Une plateforme complète pour votre activité
      </p>
      <div class="landing-trust__logos mkt-reveal" :ref="register">
        <span v-for="item in trustLogos" :key="item.label" class="landing-trust__pill">
          <i :class="item.icon"></i>
          {{ item.label }}
        </span>
      </div>
    </div>
  </section>
</template>

<script setup>
import { trustLogos } from '@/domains/marketing/config/marketingContent'
import { useScrollReveal } from '@/domains/marketing/composables/useScrollReveal'

const { register } = useScrollReveal()
</script>

<style scoped>
.landing-trust {
  padding-block: clamp(2.5rem, 5vw, 3.5rem);
  border-top: 1px solid var(--mkt-border-light);
  border-bottom: 1px solid var(--mkt-border-light);
}

.landing-trust__label {
  text-align: center;
  color: var(--mkt-light-muted);
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin: 0 0 2rem;
}

.landing-trust__logos {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.75rem;
}

.landing-trust__pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 1rem;
  border-radius: var(--mkt-radius-pill);
  background: var(--mkt-light);
  border: 1px solid var(--mkt-border-light);
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--mkt-light-text);
  transition: border-color 180ms ease, box-shadow 180ms ease;
}

.landing-trust__pill i {
  color: var(--mkt-accent);
  font-size: 0.85rem;
}

.landing-trust__pill:hover {
  border-color: color-mix(in srgb, var(--mkt-accent) 30%, transparent);
  box-shadow: 0 4px 16px var(--mkt-accent-soft);
}

@media (max-width: 400px) {
  .landing-trust {
    padding-block: 1.75rem;
  }

  .landing-trust__label {
    font-size: 0.7rem;
    margin-bottom: 1.15rem;
  }

  .landing-trust__logos {
    gap: 0.5rem;
  }

  .landing-trust__pill {
    padding: 0.45rem 0.75rem;
    font-size: 0.78rem;
    gap: 0.4rem;
  }

  .landing-trust__pill i {
    font-size: 0.75rem;
  }
}
</style>
