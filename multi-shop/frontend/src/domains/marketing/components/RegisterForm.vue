<template>
  <form class="register-form" @submit.prevent="submit">
    <div class="register-form__header">
      <h2>Créez votre compte</h2>
      <p>Quelques informations suffisent pour provisionner votre boutique.</p>
    </div>

    <span class="register-form__badge">{{ betaPlanNotice }}</span>

    <div class="register-form__field">
      <label for="accountName">Nom du compte / boutique</label>
      <InputText
        id="accountName"
        v-model="accountName"
        placeholder="Ma Boutique"
        fluid
        :invalid="!!errors.accountName"
      />
      <small v-if="errors.accountName" class="register-form__error">{{ errors.accountName }}</small>
    </div>

    <div class="register-form__field">
      <label for="accountSlug">Identifiant URL (slug)</label>
      <InputText
        id="accountSlug"
        v-model="accountSlug"
        placeholder="ma-boutique"
        fluid
        :invalid="!!errors.accountSlug"
        @input="onSlugInput"
      />
      <small v-if="errors.accountSlug" class="register-form__error">{{ errors.accountSlug }}</small>
    </div>

    <div class="register-form__field">
      <label for="adminEmail">E-mail administrateur</label>
      <InputText
        id="adminEmail"
        v-model="adminEmail"
        type="email"
        placeholder="owner@example.com"
        fluid
        :invalid="!!errors.adminEmail"
      />
      <small v-if="errors.adminEmail" class="register-form__error">{{ errors.adminEmail }}</small>
    </div>

    <Message v-if="errors.general" severity="error" size="small">
      {{ errors.general }}
    </Message>

    <Button type="submit" label="Créer mon compte" icon="pi pi-user-plus" :loading="loading" size="large" fluid />

    <p class="register-form__footer">
      Déjà un compte ?
      <RouterLink :to="{ name: 'login' }">Se connecter</RouterLink>
    </p>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import { betaPlanNotice } from '@/domains/marketing/config/marketingContent'
import { useAccountSlug } from '@/domains/marketing/composables/useAccountSlug'
import { signupService } from '@/domains/marketing/services/signupService'
import { extractApiError } from '@/domains/shared/services/http'

const emit = defineEmits(['success'])

const route = useRoute()
const accountName = ref('')
const adminEmail = ref('')
const loading = ref(false)
const errors = ref({})
const requestedPlanCode = route.query.plan ? String(route.query.plan) : null

const { accountSlug, onSlugInput } = useAccountSlug(accountName)

const validate = () => {
  const nextErrors = {}

  if (!accountName.value.trim()) {
    nextErrors.accountName = 'Le nom du compte est requis.'
  }

  if (!accountSlug.value.trim()) {
    nextErrors.accountSlug = 'Le slug est requis.'
  }

  if (!adminEmail.value.trim()) {
    nextErrors.adminEmail = 'L’e-mail administrateur est requis.'
  }

  errors.value = nextErrors

  return Object.keys(nextErrors).length === 0
}

const submit = async () => {
  if (!validate()) {
    return
  }

  loading.value = true
  errors.value = {}

  try {
    const result = await signupService.signup({
      accountName: accountName.value.trim(),
      accountSlug: accountSlug.value.trim(),
      billingEmail: adminEmail.value.trim(),
      adminEmail: adminEmail.value.trim(),
      requestedPlanCode
    })

    emit('success', result)
  } catch (error) {
    const apiError = extractApiError(error, 'Impossible de créer le compte.')
    errors.value = {
      general: apiError.message,
      ...apiError.fieldErrors
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.register-form {
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  border-radius: var(--mkt-radius);
  background: white;
  box-shadow: var(--mkt-shadow);
}

.register-form__header h2 {
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.register-form__header p,
.register-form__footer {
  color: var(--mkt-light-muted);
  font-size: 0.92rem;
}

.register-form__header p {
  margin-top: 0.35rem;
}

.register-form__badge {
  align-self: flex-start;
  padding: 0.35rem 0.75rem;
  border-radius: var(--mkt-radius-pill);
  background: var(--mkt-accent-soft);
  color: var(--mkt-accent-strong);
  font-size: 0.82rem;
  font-weight: 700;
}

.register-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.register-form__field label {
  font-size: 0.88rem;
  font-weight: 600;
}

.register-form__error {
  color: #dc2626;
  font-size: 0.82rem;
}

.register-form__footer a {
  color: var(--mkt-accent);
  font-weight: 700;
}
</style>
