<template>
  <div class="register-success">
    <div class="register-success__icon">
      <i class="pi pi-check"></i>
    </div>
    <h2>Compte créé avec succès</h2>
    <p>
      Votre espace LafiaSugu est prêt.
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
      size="large"
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
.register-success {
  --p-primary-color: var(--mkt-accent);
  --p-primary-contrast-color: #ffffff;
  --p-primary-hover-color: var(--mkt-accent-strong);
  --p-primary-active-color: var(--mkt-accent-strong);
  width: 100%;
  max-width: 100%;
  min-width: 0;
  box-sizing: border-box;
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  border-radius: var(--mkt-radius);
  background: white;
  box-shadow: var(--mkt-shadow);
  color: var(--mkt-light-text);
  color-scheme: light;
}

.register-success__icon {
  width: 3rem;
  height: 3rem;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: var(--mkt-accent-soft);
  color: var(--mkt-accent);
}

.register-success h2 {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--mkt-light-text);
}

.register-success p {
  color: var(--mkt-light-muted);
  line-height: 1.6;
}

.register-success__credentials {
  padding: 1rem;
  border-radius: 0.85rem;
  background: var(--mkt-light);
  border: 1px solid var(--mkt-border-light);
}

.register-success__credentials p {
  margin: 0;
  color: var(--mkt-light-text);
  font-size: 0.92rem;
}

.register-success__back {
  justify-content: center;
  color: var(--mkt-light-muted);
  font-size: 0.92rem;
}

.register-success__hint {
  color: var(--mkt-light-muted);
  font-size: 0.88rem;
  line-height: 1.5;
  text-align: center;
}

@media (max-width: 400px) {
  .register-success {
    padding: 0.95rem 0.8rem;
    gap: 0.75rem;
    border-radius: 0.95rem;
  }

  .register-success__icon {
    width: 2.5rem;
    height: 2.5rem;
    font-size: 0.95rem;
  }

  .register-success h2 {
    font-size: 1.15rem;
  }

  .register-success p {
    font-size: 0.85rem;
    line-height: 1.5;
  }

  .register-success__credentials {
    padding: 0.75rem;
    border-radius: 0.75rem;
  }

  .register-success__credentials p {
    font-size: 0.82rem;
  }

  .register-success__back {
    font-size: 0.82rem;
  }

  .register-success :deep(.p-button) {
    font-size: 0.85rem;
    padding: 0.65rem 0.85rem;
  }
}
</style>
