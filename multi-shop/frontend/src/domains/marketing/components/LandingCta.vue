<template>
  <section class="landing-cta mkt-section mkt-section--dark">
    <div class="landing-cta__glow" aria-hidden="true"></div>
    <div class="mkt-container landing-cta__inner mkt-reveal" :ref="register">
      <div class="landing-cta__copy">
        <p class="mkt-eyebrow">Prêt à démarrer</p>
        <h2 class="mkt-title mkt-title--sm">Lancez votre espace StockifyWeb aujourd'hui.</h2>
        <p class="mkt-lead">
          Créez votre compte en quelques minutes et recevez vos identifiants immédiatement.
          Starter gratuit pendant toute la bêta.
        </p>
      </div>
      <div class="landing-cta__actions">
        <RouterLink :to="{ name: 'register' }" class="mkt-btn mkt-btn--primary">
          Créer mon compte
          <i class="pi pi-arrow-right"></i>
        </RouterLink>
        <RouterLink :to="{ name: 'login' }" class="mkt-btn mkt-btn--ghost-light">
          J'ai déjà un compte
        </RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup>
import { useScrollReveal } from '@/domains/marketing/composables/useScrollReveal'

const { register } = useScrollReveal()
</script>

<style scoped>
.landing-cta {
  padding-block: clamp(3.5rem, 7vw, 5rem);
  overflow: hidden;
}

.landing-cta__glow {
  position: absolute;
  width: 28rem;
  height: 28rem;
  top: 50%;
  left: 50%;
  translate: -50% -50%;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, transparent 70%);
  pointer-events: none;
}

.landing-cta__inner {
  position: relative;
  z-index: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2.5rem;
  padding: clamp(2rem, 4vw, 3rem);
  border-radius: var(--mkt-radius);
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(12px);
}

.landing-cta__copy {
  display: flex;
  flex-direction: column;
  gap: 0;
  max-width: 32rem;
}

.landing-cta__copy .mkt-eyebrow {
  margin-bottom: 0.75rem;
}

.landing-cta__copy .mkt-title {
  margin-bottom: 1rem;
}

.landing-cta__copy .mkt-lead {
  margin-top: 0;
}

.landing-cta__actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .landing-cta__inner {
    flex-direction: column;
    align-items: flex-start;
  }

  .landing-cta__actions {
    width: 100%;
  }

  .landing-cta__actions .mkt-btn {
    width: 100%;
  }
}
</style>
