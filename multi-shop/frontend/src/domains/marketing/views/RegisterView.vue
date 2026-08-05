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
              Pendant la bêta, tous les nouveaux comptes démarrent sur le plan Starter.
              L'infrastructure de paiement arrivera prochainement pour activer les plans avancés.
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
  grid-template-columns: minmax(0, 1fr) minmax(20rem, 26rem);
  gap: clamp(2rem, 5vw, 4rem);
  align-items: start;
}

.register-page__intro {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
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
    grid-template-columns: 1fr;
  }
}
</style>
