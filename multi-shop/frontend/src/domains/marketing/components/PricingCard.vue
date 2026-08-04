<template>
  <article class="pricing-card" :class="{ 'pricing-card--featured': featured }">
    <span v-if="featured" class="pricing-card__badge">Recommandé</span>

    <div class="pricing-card__head">
      <p class="pricing-card__name">{{ plan.name }}</p>
      <p class="pricing-card__price">
        <strong>{{ formattedPrice }}</strong>
        <span>/ {{ billingLabel }}</span>
      </p>
    </div>

    <ul class="pricing-card__features">
      <li v-if="maxShops">
        <i class="pi pi-check"></i>
        {{ maxShops }} boutique{{ maxShops > 1 ? 's' : '' }} incluse{{ maxShops > 1 ? 's' : '' }}
      </li>
      <li v-for="feature in featureItems" :key="feature">
        <i class="pi pi-check"></i>
        {{ feature }}
      </li>
    </ul>

    <RouterLink
      :to="{ name: 'register', query: { plan: plan.code } }"
      class="pricing-card__cta"
      :class="{ 'pricing-card__cta--primary': featured }"
    >
      {{ featured ? 'Commencer gratuitement' : 'Choisir ce plan' }}
    </RouterLink>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { featureLabels } from '@/domains/marketing/config/marketingContent'

const props = defineProps({
  plan: {
    type: Object,
    required: true
  },
  featured: {
    type: Boolean,
    default: false
  }
})

const formattedPrice = computed(() => {
  if (!props.plan.priceCents) {
    return 'Gratuit'
  }

  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0
  }).format(props.plan.priceCents / 100)
})

const billingLabel = computed(() => (
  props.plan.billingPeriod === 'yearly' ? 'an' : 'mois'
))

const maxShops = computed(() => props.plan.quotas?.max_shops ?? null)

const featureItems = computed(() => (
  (props.plan.features ?? []).map((feature) => featureLabels[feature.code] ?? feature.name)
))
</script>

<style scoped>
.pricing-card {
  position: relative;
  padding: 2rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  min-height: 100%;
  border-radius: var(--mkt-radius);
  border: 1px solid var(--mkt-border-light);
  background: white;
  box-shadow: var(--mkt-shadow-sm);
  transition: transform 220ms ease, box-shadow 220ms ease;
}

.pricing-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--mkt-shadow);
}

.pricing-card--featured {
  border-color: rgba(16, 185, 129, 0.4);
  background: linear-gradient(180deg, rgba(16, 185, 129, 0.08) 0%, white 45%);
  box-shadow: 0 20px 56px rgba(16, 185, 129, 0.18);
}

.pricing-card__badge {
  position: absolute;
  top: -0.65rem;
  left: 50%;
  transform: translateX(-50%);
  padding: 0.3rem 0.85rem;
  border-radius: var(--mkt-radius-pill);
  background: var(--mkt-accent);
  color: white;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.pricing-card__name {
  color: var(--mkt-light-muted);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-size: 0.78rem;
  font-weight: 700;
}

.pricing-card__price strong {
  font-size: 2.4rem;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.pricing-card__price span {
  color: var(--mkt-light-muted);
  font-size: 0.95rem;
}

.pricing-card__features {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  flex: 1;
}

.pricing-card__features li {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  color: var(--mkt-light-muted);
  font-size: 0.92rem;
}

.pricing-card__features i {
  color: var(--mkt-accent);
  font-size: 0.85rem;
}

.pricing-card__cta {
  display: inline-flex;
  justify-content: center;
  padding: 0.85rem 1rem;
  border-radius: var(--mkt-radius-pill);
  border: 1px solid var(--mkt-border-light);
  color: var(--mkt-light-text);
  font-weight: 700;
  transition: transform 180ms ease;
}

.pricing-card__cta--primary {
  background: var(--mkt-accent);
  border-color: transparent;
  color: white;
  box-shadow: 0 10px 32px rgba(16, 185, 129, 0.3);
}
</style>
