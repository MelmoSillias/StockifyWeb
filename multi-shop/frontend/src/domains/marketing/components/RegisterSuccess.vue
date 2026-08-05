<template>
  <div class="register-success">
    <div class="register-success__icon">
      <i class="pi pi-check"></i>
    </div>
    <h2>Compte créé avec succès</h2>
    <p>
      Votre espace LafiaSugu est prêt.
      <template v-if="result.shopCredentials?.passwordProvided">
        Connectez-vous avec l’e-mail et le mot de passe que vous avez choisis.
      </template>
      <template v-else>
        Conservez ces identifiants pour votre première connexion.
      </template>
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
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  border-radius: var(--mkt-radius);
  background: white;
  box-shadow: var(--mkt-shadow);
  color: var(--mkt-light-text);
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
</style>
