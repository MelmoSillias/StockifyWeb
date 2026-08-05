<template>
  <div class="register-form">
    <div class="register-form__header">
      <h2>Créez votre compte</h2>
      <p>Quelques informations suffisent pour provisionner votre boutique.</p>
    </div>

    <span class="register-form__badge">{{ betaPlanNotice }}</span>

    <Stepper v-model:value="step" class="stepper-animated">
      <StepList>
        <Step value="1">Compte</Step>
        <Step value="2">Mot de passe</Step>
      </StepList>
      <StepPanels>
        <StepPanel v-slot="{ active }" value="1">
          <Transition :name="transitionName" mode="out-in">
            <div v-if="active" key="step-1" class="register-form__panel">
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
            </div>
          </Transition>
        </StepPanel>

        <StepPanel v-slot="{ active }" value="2">
          <Transition :name="transitionName" mode="out-in">
            <div v-if="active" key="step-2" class="register-form__panel">
            <div class="register-form__field">
              <label for="adminPassword">Mot de passe administrateur</label>
              <Password
                id="adminPassword"
                v-model="adminPassword"
                placeholder="Minimum 8 caractères"
                fluid
                toggle-mask
                :feedback="false"
                :invalid="!!errors.adminPassword"
              />
              <small v-if="errors.adminPassword" class="register-form__error">{{ errors.adminPassword }}</small>
            </div>

            <div class="register-form__field">
              <label for="adminPasswordConfirm">Confirmer le mot de passe</label>
              <Password
                id="adminPasswordConfirm"
                v-model="adminPasswordConfirm"
                placeholder="Retapez le mot de passe"
                fluid
                toggle-mask
                :feedback="false"
                :invalid="!!errors.adminPasswordConfirm"
              />
              <small v-if="errors.adminPasswordConfirm" class="register-form__error">
                {{ errors.adminPasswordConfirm }}
              </small>
            </div>
            </div>
          </Transition>
        </StepPanel>
      </StepPanels>
    </Stepper>

    <Message v-if="errors.general" severity="error" size="small">
      {{ errors.general }}
    </Message>

    <div class="register-form__actions">
      <Button
        v-if="step !== '1'"
        type="button"
        label="Précédent"
        severity="secondary"
        outlined
        @click="prevStep"
      />
      <Button
        v-if="step === '1'"
        type="button"
        label="Suivant"
        icon="pi pi-arrow-right"
        icon-pos="right"
        @click="nextStep"
      />
      <Button
        v-else
        type="button"
        label="Créer mon compte"
        icon="pi pi-user-plus"
        :loading="loading"
        :disabled="loading"
        @click="submit"
      />
    </div>

    <p class="register-form__footer">
      Déjà un compte ?
      <RouterLink :to="loginTo">Se connecter</RouterLink>
    </p>
    <p class="register-form__footer">
      <RouterLink :to="landingTo">Retour à l'accueil</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import Step from 'primevue/step'
import StepList from 'primevue/steplist'
import StepPanel from 'primevue/steppanel'
import StepPanels from 'primevue/steppanels'
import Stepper from 'primevue/stepper'
import { betaPlanNotice } from '@/domains/marketing/config/marketingContent'
import { useAccountSlug } from '@/domains/marketing/composables/useAccountSlug'
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'
import { signupService } from '@/domains/marketing/services/signupService'
import { extractApiError } from '@/domains/shared/services/http'
import { useAsyncAction } from '@/domains/shared/composables/useAsyncAction'

const emit = defineEmits(['success'])

const route = useRoute()
const { loginTo, landingTo } = useMarketingAuth()
const step = ref('1')
const stepDirection = ref('forward')
const transitionName = computed(() => (
  stepDirection.value === 'forward' ? 'step-slide-forward' : 'step-slide-back'
))
const accountName = ref('')
const adminEmail = ref('')
const adminPassword = ref('')
const adminPasswordConfirm = ref('')
const errors = ref({})
const requestedPlanCode = route.query.plan ? String(route.query.plan) : null

const { accountSlug, onSlugInput } = useAccountSlug(accountName)

const validateAccountStep = () => {
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

const validatePasswordStep = () => {
  const nextErrors = {}

  if (!adminPassword.value) {
    nextErrors.adminPassword = 'Le mot de passe est requis.'
  } else if (adminPassword.value.length < 8) {
    nextErrors.adminPassword = 'Le mot de passe doit contenir au moins 8 caractères.'
  }

  if (!adminPasswordConfirm.value) {
    nextErrors.adminPasswordConfirm = 'Veuillez confirmer le mot de passe.'
  } else if (adminPasswordConfirm.value !== adminPassword.value) {
    nextErrors.adminPasswordConfirm = 'Les mots de passe ne correspondent pas.'
  }

  errors.value = nextErrors

  return Object.keys(nextErrors).length === 0
}

const prevStep = () => {
  stepDirection.value = 'back'
  step.value = '1'
  errors.value = {}
}

const nextStep = () => {
  if (!validateAccountStep()) {
    return
  }

  errors.value = {}
  stepDirection.value = 'forward'
  step.value = '2'
}

const { pending: loading, run: submit } = useAsyncAction(async () => {
  if (!validatePasswordStep()) {
    return
  }

  errors.value = {}

  try {
    const result = await signupService.signup({
      accountName: accountName.value.trim(),
      accountSlug: accountSlug.value.trim(),
      billingEmail: adminEmail.value.trim(),
      adminEmail: adminEmail.value.trim(),
      adminPassword: adminPassword.value,
      requestedPlanCode
    })

    emit('success', result)
  } catch (error) {
    const apiError = extractApiError(error, 'Impossible de créer le compte.')
    errors.value = {
      general: apiError.message,
      ...apiError.fieldErrors
    }
  }
})
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
  color: var(--mkt-light-text);
}

.register-form__header h2 {
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--mkt-light-text);
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

.register-form__panel {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding-top: 0.5rem;
}

.register-form :deep(.p-steppanels) {
  overflow: hidden;
  min-height: 12rem;
}

.register-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.register-form__field label {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--mkt-light-text);
}

.register-form__error {
  color: #dc2626;
  font-size: 0.82rem;
}

.register-form__actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.register-form__footer a {
  color: var(--mkt-accent);
  font-weight: 700;
}

.step-slide-forward-enter-active,
.step-slide-forward-leave-active,
.step-slide-back-enter-active,
.step-slide-back-leave-active {
  transition: opacity 240ms ease, transform 240ms cubic-bezier(0.22, 1, 0.36, 1);
}

.step-slide-forward-enter-from {
  opacity: 0;
  transform: translateX(14px);
}

.step-slide-forward-leave-to {
  opacity: 0;
  transform: translateX(-14px);
}

.step-slide-back-enter-from {
  opacity: 0;
  transform: translateX(-14px);
}

.step-slide-back-leave-to {
  opacity: 0;
  transform: translateX(14px);
}

@media (prefers-reduced-motion: reduce) {
  .step-slide-forward-enter-active,
  .step-slide-forward-leave-active,
  .step-slide-back-enter-active,
  .step-slide-back-leave-active {
    transition: none;
  }

  .step-slide-forward-enter-from,
  .step-slide-forward-leave-to,
  .step-slide-back-enter-from,
  .step-slide-back-leave-to {
    transform: none;
  }
}
</style>
