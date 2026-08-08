<template>
  <section class="landing-cta mkt-section mkt-section--primary-soft">
    <div class="mkt-container landing-cta__inner">
      <div class="landing-cta__copy">
        <h2 class="mkt-title mkt-title--sm">Créez votre espace de gestion en quelques minutes</h2>
        <p class="mkt-lead">
          Inscrivez-vous, configurez votre boutique et commencez à suivre
          votre stock et vos ventes. Plan Starter avec 1 mois d'essai gratuit.
        </p>
      </div>
      <div class="landing-cta__actions">
        <RouterLink :to="primaryAction.to" class="mkt-btn mkt-btn--primary">
          {{ isAuthenticated ? 'Accéder au tableau de bord' : 'Créer mon compte' }}
        </RouterLink>
        <RouterLink v-if="!isAuthenticated" :to="loginTo" class="mkt-btn mkt-btn--secondary">
          J'ai déjà un compte
        </RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup>
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'

const { isAuthenticated, loginTo, primaryAction } = useMarketingAuth()
</script>

<style scoped>
.landing-cta__inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
  padding: 1.75rem;
  border: 1px solid color-mix(in srgb, var(--mkt-primary) 15%, var(--mkt-border));
  border-radius: var(--mkt-radius);
  background: var(--mkt-surface);
}

.landing-cta__copy {
  max-width: 32rem;
}

.landing-cta__copy .mkt-lead {
  margin-top: 0.75rem;
}

.landing-cta__actions {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
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

@media (max-width: 400px) {
  .landing-cta__inner {
    padding: 1.25rem;
  }
}
</style>
