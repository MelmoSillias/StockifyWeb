<template>
  <article class="pricing-card" :class="{ 'pricing-card--featured': featured }">
    <span v-if="featured" class="pricing-card__badge">Recommandé</span>
    <span v-if="isStarter" class="pricing-card__trial">1 mois d'essai gratuit</span>

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
      <li v-if="maxUsers">
        <i class="pi pi-check"></i>
        {{ maxUsers }} utilisateur{{ maxUsers > 1 ? 's' : '' }}
      </li>
      <li v-for="feature in featureItems" :key="feature">
        <i class="pi pi-check"></i>
        {{ feature }}
      </li>
      <li v-if="!featureItems.length && isStarter">
        <i class="pi pi-check"></i>
        Stock, panier, ventes et paiements
      </li>
    </ul>

    <RouterLink
      :to="ctaTo"
      class="pricing-card__cta"
      :class="{ 'pricing-card__cta--primary': featured }"
    >
      {{ ctaLabel }}
    </RouterLink>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { featureLabels } from '@/domains/marketing/config/marketingContent'
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'

const { isAuthenticated, signupTo } = useMarketingAuth()

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

const isStarter = computed(() => props.plan.code === 'starter')

const formattedPrice = computed(() => {
  const amount = props.plan.priceFcfa ?? props.plan.priceCents ?? 0
  if (!amount) {
    return 'Gratuit'
  }

  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0
  }).format(amount)
})

const billingLabel = computed(() => (
  props.plan.billingPeriod === 'yearly' ? 'an' : 'mois'
))

const maxShops = computed(() => props.plan.quotas?.max_shops ?? null)
const maxUsers = computed(() => props.plan.quotas?.max_users ?? null)

const featureItems = computed(() => (
  (props.plan.features ?? []).map((feature) => featureLabels[feature.code] ?? feature.name)
))

const ctaTo = signupTo({ plan: props.plan.code })

const ctaLabel = computed(() => {
  if (isAuthenticated.value) {
    return 'Accéder au tableau de bord'
  }

  if (isStarter.value) {
    return 'Commencer gratuitement'
  }

  return props.featured ? 'Choisir Essentiels' : 'Choisir ce plan'
})
</script>

<style scoped>
.pricing-card {
  position: relative;
  padding: 1.5rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-height: 100%;
  border-radius: var(--mkt-radius);
  border: 1px solid var(--mkt-border);
  background: var(--mkt-surface);
  box-shadow: var(--mkt-shadow);
}

.pricing-card--featured {
  border-color: var(--mkt-primary);
}

.pricing-card__badge {
  position: absolute;
  top: -0.55rem;
  left: 50%;
  transform: translateX(-50%);
  padding: 0.2rem 0.65rem;
  border-radius: var(--mkt-radius-sm);
  background: var(--mkt-primary);
  color: white;
  font-size: 0.6875rem;
  font-weight: 600;
}

.pricing-card__trial {
  align-self: flex-start;
  padding: 0.2rem 0.55rem;
  border-radius: var(--mkt-radius-sm);
  background: var(--mkt-primary-soft);
  color: var(--mkt-primary);
  font-size: 0.75rem;
  font-weight: 600;
}

.pricing-card__name {
  color: var(--mkt-text-muted);
  font-size: 0.8125rem;
  font-weight: 600;
}

.pricing-card__price strong {
  font-size: 1.75rem;
  font-weight: 600;
}

.pricing-card__price span {
  color: var(--mkt-text-muted);
  font-size: 0.875rem;
}

.pricing-card__features {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
}

.pricing-card__features li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--mkt-text-muted);
  font-size: 0.875rem;
}

.pricing-card__features i {
  color: var(--mkt-primary);
  font-size: 0.8125rem;
}

.pricing-card__cta {
  display: inline-flex;
  justify-content: center;
  padding: 0.625rem 1rem;
  border-radius: var(--mkt-radius);
  border: 1px solid var(--mkt-border);
  color: var(--mkt-text);
  font-weight: 600;
  font-size: 0.9375rem;
  text-decoration: none;
  transition: background 160ms ease, border-color 160ms ease;
}

.pricing-card__cta--primary {
  background: var(--mkt-primary);
  border-color: transparent;
  color: white;
}

.pricing-card__cta--primary:hover {
  background: var(--mkt-primary-strong);
}

@media (max-width: 400px) {
  .pricing-card {
    padding: 1.125rem 1rem;
  }

  .pricing-card__price strong {
    font-size: 1.5rem;
  }
}
</style>
