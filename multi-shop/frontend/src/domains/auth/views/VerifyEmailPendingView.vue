<template>
  <div class="verify-pending">
    <div class="verify-pending__card">
      <div class="verify-pending__icon">
        <i class="pi pi-envelope"></i>
      </div>

      <h1>Vérifiez votre adresse e-mail</h1>

      <p v-if="verifiedNotice" class="verify-pending__success">
        Votre adresse e-mail a été confirmée. Actualisez cette page ou reconnectez-vous pour accéder à l'application.
      </p>

      <p v-else>
        Un e-mail de confirmation a été envoyé à
        <strong>{{ authStore.user?.email || 'votre adresse' }}</strong>.
        Cliquez sur le lien reçu pour activer votre compte.
      </p>

      <Button
        label="Renvoyer l'e-mail"
        icon="pi pi-refresh"
        :loading="resending"
        :disabled="resending"
        @click="handleResend"
      />

      <Button
        label="Actualiser mon statut"
        icon="pi pi-sync"
        severity="secondary"
        outlined
        :loading="refreshing"
        :disabled="refreshing"
        @click="handleRefresh"
      />

      <Button
        label="Se déconnecter"
        icon="pi pi-sign-out"
        severity="secondary"
        text
        @click="handleLogout"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import { useAuthStore } from '@/domains/auth/stores/auth'
import { authService } from '@/domains/auth/services/authService'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const resending = ref(false)
const refreshing = ref(false)

const verifiedNotice = computed(() => route.query.verified === '1')

const handleResend = async () => {
  resending.value = true
  try {
    await authService.resendVerificationEmail()
    toast.add({
      severity: 'success',
      summary: 'E-mail envoyé',
      detail: 'Si votre compte nécessite une vérification, un nouvel e-mail a été envoyé.',
      life: 4000
    })
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Envoi impossible',
      detail: 'Impossible de renvoyer l\'e-mail pour le moment.',
      life: 4000
    })
  } finally {
    resending.value = false
  }
}

const handleRefresh = async () => {
  refreshing.value = true
  try {
    await authService.syncVerificationStatus()
    await authStore.fetchCurrentUser()
    if (authStore.isEmailVerified) {
      await router.replace({ name: 'home' })
      return
    }

    toast.add({
      severity: 'warn',
      summary: 'Statut inchangé',
      detail: 'Votre e-mail n\'est pas encore vérifié.',
      life: 3000
    })
  } catch {
    toast.add({
      severity: 'warn',
      summary: 'Statut inchangé',
      detail: 'Votre e-mail n\'est pas encore vérifié.',
      life: 3000
    })
  } finally {
    refreshing.value = false
  }
}

const handleLogout = async () => {
  authStore.logout()
  await router.replace({ name: 'login' })
}
</script>

<style scoped>
.verify-pending {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 1.5rem;
  background: var(--layout-shell-bg, #f5f7fb);
}

.verify-pending__card {
  width: min(100%, 28rem);
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 2rem;
  border-radius: 1rem;
  background: white;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
  text-align: center;
}

.verify-pending__icon {
  width: 3rem;
  height: 3rem;
  margin: 0 auto;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: color-mix(in srgb, var(--layout-accent, #2563eb) 12%, white);
  color: var(--layout-accent, #2563eb);
}

.verify-pending h1 {
  margin: 0;
  font-size: 1.5rem;
}

.verify-pending p {
  margin: 0;
  line-height: 1.6;
  color: #64748b;
}

.verify-pending__success {
  color: #15803d;
}
</style>
