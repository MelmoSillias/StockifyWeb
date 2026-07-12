<template>
  <div class="login-view">
    <div class="login-view__orb login-view__orb--primary"></div>
    <div class="login-view__orb login-view__orb--secondary"></div>

    <div class="login-view__toolbar">
      <Button
        icon="pi pi-palette"
        label="Personnaliser"
        severity="secondary"
        outlined
        @click="preferencesVisible = true"
      />
    </div>

    <div class="login-view__content">
      <section class="login-view__hero">
        <div class="login-view__brand-mark">
          <img v-if="brand.logoUrl" :src="brand.logoUrl" :alt="brand.name" class="login-view__brand-image" />
          <span v-else>{{ brand.shortName }}</span>
        </div>
        <p class="login-view__eyebrow">Console plateforme Stockify</p>
        <h1 class="login-view__title">{{ brand.name }}</h1>
        <p class="login-view__subtitle">{{ authIntro }}</p>
      </section>

      <section class="login-card">
        <div class="login-card__header">
          <h2>Connexion</h2>
          <p>Saisissez vos identifiants pour continuer.</p>
        </div>

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
            <Message v-if="errors.email" severity="error" size="small" variant="simple">
              {{ errors.email }}
            </Message>
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
            <Message v-if="errors.password" severity="error" size="small" variant="simple">
              {{ errors.password }}
            </Message>
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
            fluid
            size="large"
          />
        </form>
      </section>
    </div>

    <AppThemePanel v-model="preferencesVisible" />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/domains/auth/stores/auth'
import AppThemePanel from '@/domains/layout/components/AppThemePanel.vue'
import { appShellBrand } from '@/domains/layout/config/appLayout'
import { clearAuthFieldError, normalizeAuthError, validateLoginCredentials } from '@/domains/auth/services/authErrors'

import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import SelectButton from 'primevue/selectbutton'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const brand = appShellBrand
const preferencesVisible = ref(false)
const authIntro = 'Administration centralisée de la plateforme Stockify.'

const loginMethod = ref('email')
const loginMethodOptions = [
  { label: 'E-mail', value: 'email' },
  { label: "Nom d'utilisateur", value: 'username' }
]

const identifierLabel = computed(() => (
  loginMethod.value === 'email' ? 'Adresse e-mail' : "Nom d'utilisateur"
))
const identifierPlaceholder = computed(() => (
  loginMethod.value === 'email' ? 'admin@stockify.local' : 'admin'
))
const identifierFieldId = computed(() => (
  loginMethod.value === 'email' ? 'login-email' : 'login-username'
))

const credentials = ref({
  email: '',
  password: ''
})

const errors = ref({})
const loading = ref(false)

const clearFieldError = (fieldName) => {
  errors.value = clearAuthFieldError(errors.value, fieldName)
}

const onLoginMethodChange = () => {
  credentials.value.email = ''
  errors.value = clearAuthFieldError(errors.value, 'email')
}

const handleLogin = async () => {
  errors.value = {}

  const validation = validateLoginCredentials(credentials.value, loginMethod.value)

  if (!validation.valid) {
    errors.value = {
      ...validation.fieldErrors,
      general: 'Veuillez corriger les champs en erreur.'
    }
    return
  }

  loading.value = true

  try {
    await authStore.login(credentials.value)
    toast.add({
      severity: 'success',
      summary: 'Connexion réussie',
      detail: 'Bienvenue !',
      life: 3000
    })

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
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-view {
  position: relative;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  padding: clamp(1rem, 3vw, 2rem);
  background:
    radial-gradient(circle at top left, color-mix(in srgb, var(--layout-accent-soft) 85%, transparent), transparent 32%),
    radial-gradient(circle at bottom right, color-mix(in srgb, var(--layout-panel-border) 60%, transparent), transparent 30%),
    var(--layout-shell-bg);
  color: var(--layout-text-color);
  overflow: hidden;
}

.login-view__toolbar {
  display: flex;
  justify-content: flex-end;
  position: relative;
  z-index: 1;
}

.login-view__content {
  position: relative;
  z-index: 1;
  flex: 1;
  width: min(100%, 72rem);
  margin: 0 auto;
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(20rem, 28rem);
  align-items: center;
  gap: clamp(1.5rem, 4vw, 4rem);
}

.login-view__hero {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.login-view__brand-mark {
  width: clamp(4rem, 8vw, 5.5rem);
  height: clamp(4rem, 8vw, 5.5rem);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: calc(var(--layout-radius-lg) * 1.1);
  background: linear-gradient(135deg, var(--layout-accent), var(--layout-accent-strong));
  color: white;
  font-size: clamp(1rem, 1rem + 1vw, 1.5rem);
  font-weight: 700;
  box-shadow: var(--layout-shadow-soft);
  overflow: hidden;
}

.login-view__brand-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 0.5rem;
}

.login-view__eyebrow {
  margin: 0;
  font-size: var(--layout-font-size-sm);
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--layout-text-muted);
}

.login-view__title {
  margin: 0;
  font-size: clamp(2.2rem, 1.8rem + 1.8vw, 4rem);
  line-height: 1;
}

.login-view__subtitle {
  margin: 0;
  max-width: 36rem;
  font-size: var(--layout-font-size-lg);
  line-height: 1.6;
  color: var(--layout-text-muted);
}

.login-card {
  position: relative;
  padding: clamp(1.25rem, 3vw, 2rem);
  background: color-mix(in srgb, var(--layout-panel-bg) 94%, transparent);
  border: 1px solid var(--layout-panel-border);
  border-radius: var(--layout-radius-lg);
  backdrop-filter: blur(18px);
  box-shadow: var(--layout-shadow-soft);
}

.login-card__header {
  margin-bottom: 1.25rem;
}

.login-card__header h2 {
  margin: 0;
  font-size: var(--layout-font-size-2xl);
}

.login-card__header p {
  margin: 0.35rem 0 0;
  color: var(--layout-text-muted);
}

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
  font-size: var(--layout-font-size-sm);
  font-weight: 600;
}

.login-card :deep(.p-inputtext),
.login-card :deep(.p-password-input) {
  background: color-mix(in srgb, var(--layout-panel-bg) 92%, transparent);
  color: var(--layout-text-color);
  border-color: var(--layout-panel-border);
}

.login-card :deep(.p-inputtext::placeholder),
.login-card :deep(.p-password-input::placeholder) {
  color: var(--layout-text-muted);
}

.login-card :deep(.p-password .p-password-toggle-mask-icon) {
  color: var(--layout-text-muted);
}

.login-card__message {
  text-align: center;
}

.login-view__orb {
  position: absolute;
  border-radius: 999px;
  filter: blur(70px);
  opacity: 0.45;
  pointer-events: none;
}

.login-view__orb--primary {
  top: -4rem;
  left: -4rem;
  width: 14rem;
  height: 14rem;
  background: color-mix(in srgb, var(--layout-accent) 40%, transparent);
}

.login-view__orb--secondary {
  right: -3rem;
  bottom: -3rem;
  width: 12rem;
  height: 12rem;
  background: color-mix(in srgb, var(--layout-accent-strong) 28%, transparent);
}

@media (max-width: 900px) {
  .login-view__content {
    grid-template-columns: 1fr;
    align-items: start;
    padding-top: 2rem;
  }

  .login-view__hero {
    gap: 0.75rem;
  }
}
</style>
