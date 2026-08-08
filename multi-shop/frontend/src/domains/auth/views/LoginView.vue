<template>
  <AuthPageLayout
    title="Connexion"
    subtitle="Accédez à votre espace de gestion commerciale."
  >
    <section class="login-card auth-light-surface">
      <form @submit.prevent="handleLogin" class="login-card__form">
        <div class="login-card__field">
          <span class="login-card__field-label">Identifiant</span>
          <SelectButton
            v-model="loginMethod"
            :options="loginMethodOptions"
            option-label="label"
            option-value="value"
            fluid
            @update:model-value="onLoginMethodChange"
          />
        </div>

        <div class="login-card__field">
          <label :for="identifierFieldId">{{ identifierLabel }}</label>
          <InputText
            v-model="credentials.email"
            :id="identifierFieldId"
            :type="loginMethod === 'email' ? 'email' : 'text'"
            :placeholder="identifierPlaceholder"
            autocomplete="username"
            fluid
            :invalid="!!errors.email"
            @update:modelValue="clearFieldError('email')"
          />
        </div>

        <div class="login-card__field">
          <label for="password">Mot de passe</label>
          <Password
            v-model="credentials.password"
            id="password"
            placeholder="••••••••"
            fluid
            toggleMask
            :feedback="false"
            :invalid="!!errors.password"
            @update:modelValue="clearFieldError('password')"
          />
        </div>

        <div v-if="errors.general" class="login-card__message">
          <Message severity="error" size="small" variant="simple">
            {{ errors.general }}
          </Message>
        </div>

        <Button
          type="submit"
          label="Se connecter"
          :loading="loading"
          :disabled="loading"
          fluid
        />

        <p class="login-card__footer">
          Pas encore de compte ?
          <RouterLink :to="{ name: 'register' }">Créer un compte</RouterLink>
        </p>
      </form>
    </section>
  </AuthPageLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/domains/auth/stores/auth'
import AuthPageLayout from '@/domains/auth/components/AuthPageLayout.vue'
import { clearAuthFieldError, normalizeAuthError, validateLoginCredentials } from '@/domains/auth/services/authErrors'
import { useAsyncAction } from '@/domains/shared/composables/useAsyncAction'

import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import SelectButton from 'primevue/selectbutton'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const loginMethod = ref('email')
const loginMethodOptions = [
  { label: 'E-mail', value: 'email' },
  { label: "Nom d'utilisateur", value: 'username' }
]

const identifierLabel = computed(() => (
  loginMethod.value === 'email' ? 'Adresse e-mail' : "Nom d'utilisateur"
))
const identifierPlaceholder = computed(() => (
  loginMethod.value === 'email' ? 'votre@email.com' : 'votre_identifiant'
))
const identifierFieldId = computed(() => (
  loginMethod.value === 'email' ? 'login-email' : 'login-username'
))

const credentials = ref({
  email: '',
  password: ''
})

watch(
  () => route.query,
  (query) => {
    if (typeof query.email === 'string' && query.email) {
      credentials.value.email = query.email
      loginMethod.value = 'email'
    }

    if (query.verified === '1') {
      toast.add({
        severity: 'success',
        summary: 'E-mail vérifié',
        detail: 'Connectez-vous pour accéder à votre espace.',
        life: 5000
      })
    }
  },
  { immediate: true, deep: true }
)

const errors = ref({})

const clearFieldError = (fieldName) => {
  errors.value = clearAuthFieldError(errors.value, fieldName)
}

const onLoginMethodChange = () => {
  credentials.value.email = ''
  errors.value = clearAuthFieldError(errors.value, 'email')
}

const { pending: loading, run: handleLogin } = useAsyncAction(async () => {
  errors.value = {}

  const validation = validateLoginCredentials(credentials.value, loginMethod.value)

  if (!validation.valid) {
    errors.value = {
      ...validation.fieldErrors,
      general: 'Veuillez corriger les champs en erreur.'
    }
    return
  }

  try {
    await authStore.login(credentials.value)
    toast.add({
      severity: 'success',
      summary: 'Connexion réussie',
      detail: 'Bienvenue !',
      life: 3000
    })

    if (!authStore.isEmailVerified) {
      await router.replace({ name: 'verify-email-pending' })
      return
    }

    await router.replace(route.query.redirect || { name: 'home' })
  } catch (error) {
    const authError = normalizeAuthError(error, 'login')

    errors.value = {
      ...authError.fieldErrors,
      ...(authError.general ? { general: authError.general } : {})
    }

    if (authError.toast) {
      toast.add(authError.toast)
    }
  }
})
</script>

<style scoped>
.login-card__form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.login-card__field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.login-card__field label,
.login-card__field-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--mkt-text);
}

.login-card__message {
  text-align: center;
}

.login-card__footer {
  margin: 0;
  text-align: center;
  color: var(--mkt-text-muted);
  font-size: 0.875rem;
}

.login-card__footer a {
  color: var(--mkt-primary);
  font-weight: 600;
  text-decoration: none;
}

.login-card__footer a:hover {
  color: var(--mkt-primary-strong);
}
</style>
