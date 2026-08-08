<template>
  <section class="landing-hero mkt-section mkt-section--light">
    <div class="mkt-container landing-hero__grid">
      <div class="landing-hero__copy">
        <h1 class="mkt-title">
          Lafia Sugu, votre gestion commerciale simplement.
        </h1>
        <p class="mkt-lead">
          Gérez votre stock, vos ventes, vos clients et vos fournisseurs
          au quotidien. Un seul outil pour suivre l'activité de votre commerce au Mali.
        </p>
        <div class="landing-hero__actions">
          <RouterLink :to="primaryAction.to" class="mkt-btn mkt-btn--primary">
            {{ isAuthenticated ? 'Accéder au tableau de bord' : 'Créer mon compte' }}
          </RouterLink>
          <RouterLink :to="anchorTo('tarifs')" class="mkt-btn mkt-btn--secondary">
            Voir les tarifs
          </RouterLink>
        </div>
      </div>

      <div class="landing-hero__preview">
        <AppPreview title="Tableau de bord — Lafia Sugu">
          <MockupDashboard
            theme="light"
            title="Vue d'ensemble"
            :stats="[
              { label: 'Stock actif', value: '1 284' },
              { label: 'Ventes du jour', value: '48' }
            ]"
            :rows="['Huile 5L — 12 restants', 'Riz 25kg — réappro demain', 'Savon — stock OK']"
          />
        </AppPreview>
      </div>
    </div>
  </section>
</template>

<script setup>
import AppPreview from '@/domains/marketing/components/AppPreview.vue'
import MockupDashboard from '@/domains/marketing/components/MockupDashboard.vue'
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'

const { isAuthenticated, primaryAction, anchorTo } = useMarketingAuth()
</script>

<style scoped>
.landing-hero__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: clamp(2rem, 4vw, 3.5rem);
  align-items: center;
}

.landing-hero__copy {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.landing-hero__copy .mkt-lead {
  margin-top: 1rem;
  margin-bottom: 1.5rem;
  max-width: 30rem;
}

.landing-hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.landing-hero__preview {
  display: flex;
  justify-content: center;
}

@media (max-width: 900px) {
  .landing-hero__grid {
    grid-template-columns: 1fr;
  }

  .landing-hero__preview {
    margin-top: 0.5rem;
  }
}

@media (max-width: 400px) {
  .landing-hero__actions {
    flex-direction: column;
  }

  .landing-hero__actions .mkt-btn {
    width: 100%;
  }
}
</style>
