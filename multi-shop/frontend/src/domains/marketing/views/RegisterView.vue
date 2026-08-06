<template>
  <div class="mkt-page register-page">
    <MarketingShell landing>
      <section class="register-page__hero mkt-section mkt-section--dark">
        <div class="mkt-container register-page__grid">
          <div class="register-page__intro">
            <RouterLink :to="landingTo" class="register-page__back mkt-link">
              <i class="pi pi-arrow-left"></i>
              Retour à l'accueil
            </RouterLink>
            <span class="mkt-tag">Inscription</span>
            <h1 class="mkt-title mkt-title--sm">Rejoignez LafiaSugu</h1>
            <p class="mkt-lead">
              Tous les nouveaux comptes démarrent sur le plan Starter avec 1 mois d’essai gratuit.
              L’infrastructure de paiement arrivera prochainement pour activer les plans avancés.
            </p>
          </div>

          <RegisterSuccess v-if="result" :result="result" />
          <RegisterForm v-else @success="result = $event" />
        </div>
      </section>
    </MarketingShell>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import MarketingShell from '@/domains/marketing/components/MarketingShell.vue'
import RegisterForm from '@/domains/marketing/components/RegisterForm.vue'
import RegisterSuccess from '@/domains/marketing/components/RegisterSuccess.vue'
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'

const result = ref(null)
const { landingTo } = useMarketingAuth()
</script>

<style>
@import '@/domains/marketing/assets/marketing.css';
</style>

<style scoped>
.register-page__hero {
  min-height: 100vh;
  display: flex;
  align-items: flex-start;
}

.register-page__grid {
  display: grid;
  grid-template-columns: minmax(0, 22rem) minmax(0, 26rem);
  gap: clamp(1.75rem, 3vw, 3rem);
  justify-content: center;
  align-items: start;
  width: 100%;
  max-width: 56rem;
  min-width: 0;
}

.register-page__intro {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  min-width: 0;
}

.register-page__back {
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.92rem;
}

.register-page__back:hover {
  color: white;
}

@media (max-width: 900px) {
  .register-page__grid {
    grid-template-columns: minmax(0, 1fr);
    max-width: 26rem;
  }
}

@media (max-width: 400px) {
  .register-page__grid {
    gap: 1.25rem;
    max-width: 100%;
  }

  .register-page__intro {
    gap: 0.85rem;
  }

  .register-page__back {
    font-size: 0.85rem;
  }
}
</style>
