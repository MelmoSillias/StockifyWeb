<template>
  <div class="register-success auth-light-surface">
    <div class="register-success__icon">
      <i class="pi pi-check"></i>
    </div>
    <h2>Compte créé avec succès</h2>
    <p>
      Votre espace Lafia Sugu est prêt.
      Consultez votre boîte mail et cliquez sur le lien de confirmation pour activer votre compte.
    </p>

    <div class="register-success__credentials">
      <p><strong>E-mail :</strong> {{ result.shopCredentials?.email }}</p>
      <p v-if="result.shopCredentials?.temporaryPassword">
        <strong>Mot de passe temporaire :</strong> {{ result.shopCredentials.temporaryPassword }}
      </p>
    </div>

    <Button
      label="Se connecter"
      icon="pi pi-sign-in"
      fluid
      @click="goToLogin"
    />

    <p class="register-success__hint">
      Après connexion, vous serez guidé vers la page de vérification si votre e-mail n'est pas encore confirmé.
    </p>

    <RouterLink :to="landingTo" class="register-success__back mkt-link">
      <i class="pi pi-arrow-left"></i>
      Retour à l'accueil
    </RouterLink>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'

const props = defineProps({
  result: {
    type: Object,
    required: true
  }
})

const router = useRouter()
const { landingTo } = useMarketingAuth()

const goToLogin = () => {
  router.push({
    name: 'login',
    query: {
      email: props.result.shopCredentials?.email || undefined
    }
  })
}
</script>

<style scoped>
.register-success__icon {
  width: 2.75rem;
  height: 2.75rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--mkt-primary-soft);
  color: var(--mkt-primary);
}

.register-success h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--mkt-text);
}

.register-success p {
  color: var(--mkt-text-muted);
  line-height: 1.6;
  font-size: 0.9375rem;
}

.register-success__credentials {
  padding: 0.875rem;
  border-radius: var(--mkt-radius-sm);
  background: var(--mkt-surface-muted);
  border: 1px solid var(--mkt-border);
}

.register-success__credentials p {
  margin: 0;
  color: var(--mkt-text);
  font-size: 0.875rem;
}

.register-success__back {
  justify-content: center;
  color: var(--mkt-text-muted);
  font-size: 0.875rem;
}

.register-success__hint {
  color: var(--mkt-text-muted);
  font-size: 0.8125rem;
  line-height: 1.5;
  text-align: center;
}

@media (max-width: 400px) {
  .register-success h2 {
    font-size: 1.125rem;
  }

  .register-success p {
    font-size: 0.875rem;
  }
}
</style>
