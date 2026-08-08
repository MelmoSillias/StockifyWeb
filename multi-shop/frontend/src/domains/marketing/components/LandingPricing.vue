<template>
  <section id="tarifs" class="landing-pricing mkt-section mkt-section--light">
    <div class="mkt-container">
      <div class="mkt-section-header mkt-section-header--center">
        <p class="mkt-eyebrow">Tarifs</p>
        <h2 class="mkt-title mkt-title--sm">Des formules adaptées à votre activité</h2>
        <p class="mkt-lead">
          Commencez avec 1 mois d'essai gratuit sur Starter, puis choisissez
          la formule qui correspond à votre commerce.
        </p>
      </div>

      <div v-if="loading" class="landing-pricing__state">
        <Skeleton v-for="index in 4" :key="index" height="18rem" class="landing-pricing__skeleton" />
      </div>

      <div v-else-if="error" class="landing-pricing__state landing-pricing__error">
        <p>{{ error }}</p>
        <Button label="Réessayer" icon="pi pi-refresh" @click="loadPlans" />
      </div>

      <div v-else class="landing-pricing__grid">
        <PricingCard
          v-for="plan in plans"
          :key="plan.id"
          :plan="plan"
          :featured="plan.code === 'essentiels'"
        />
        <QuoteRequestCard />
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import PricingCard from '@/domains/marketing/components/PricingCard.vue'
import QuoteRequestCard from '@/domains/marketing/components/QuoteRequestCard.vue'
import { plansService } from '@/domains/marketing/services/plansService'

const plans = ref([])
const loading = ref(true)
const error = ref('')

const loadPlans = async () => {
  loading.value = true
  error.value = ''

  try {
    plans.value = await plansService.list()
  } catch (err) {
    error.value = err?.response?.data?.error
      || 'Impossible de charger les tarifs pour le moment. Veuillez réessayer.'
  } finally {
    loading.value = false
  }
}

onMounted(loadPlans)
</script>

<style scoped>
.landing-pricing__grid,
.landing-pricing__state {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr));
  gap: 1rem;
  max-width: 72rem;
  margin-inline: auto;
}

.landing-pricing__error {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: center;
  text-align: center;
  color: var(--mkt-text-muted);
}

.landing-pricing__skeleton {
  border-radius: var(--mkt-radius);
}

@media (max-width: 400px) {
  .landing-pricing__grid,
  .landing-pricing__state {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
