<template>
  <AuthPageLayout title="Vérifiez votre adresse e-mail">
    <div class="verify-pending auth-light-surface">
      <div class="verify-pending__icon">
        <i class="pi pi-envelope"></i>
      </div>

      <p v-if="verifiedNotice" class="verify-pending__success">
        Votre adresse e-mail a été confirmée. Actualisez cette page ou reconnectez-vous pour accéder à l'application.
      </p>

      <template v-else>
        <p class="verify-pending__intro">
          Un e-mail de confirmation a été envoyé à
          <strong>{{ authStore.user?.email || 'votre adresse' }}</strong>.
        </p>
        <p class="verify-pending__steps">
          Ouvrez le lien reçu pour activer votre compte, puis reconnectez-vous.
        </p>
      </template>

      <div class="verify-pending__actions">
        <Button
          label="Renvoyer l'e-mail"
          icon="pi pi-refresh"
          :loading="resending"
          :disabled="resending"
          fluid
          @click="handleResend"
        />

        <Button
          label="Actualiser mon statut"
          icon="pi pi-sync"
          severity="secondary"
          outlined
          fluid
          :loading="refreshing"
          :disabled="refreshing"
          @click="handleRefresh"
        />

        <Button
          label="Se déconnecter"
          icon="pi pi-sign-out"
          severity="secondary"
          text
          fluid
          @click="handleLogout"
        />
      </div>
    </div>
  </AuthPageLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import AuthPageLayout from '@/domains/auth/components/AuthPageLayout.vue'
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
  text-align: center;
}

.verify-pending__icon {
  width: 2.75rem;
  height: 2.75rem;
  margin: 0 auto;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--mkt-primary-soft);
  color: var(--mkt-primary);
}

.verify-pending__intro,
.verify-pending__steps {
  margin: 0;
  line-height: 1.6;
  color: var(--mkt-text-muted);
  font-size: 0.9375rem;
}

.verify-pending strong {
  color: var(--mkt-text);
  word-break: break-word;
}

.verify-pending__steps {
  font-size: 0.875rem;
}

.verify-pending__success {
  margin: 0;
  color: var(--mkt-success);
  line-height: 1.6;
  font-size: 0.9375rem;
}

.verify-pending__actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.25rem;
}
</style>
