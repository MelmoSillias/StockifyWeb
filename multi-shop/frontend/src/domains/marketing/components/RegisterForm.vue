<template>
  <div class="register-form auth-light-surface">
    <div class="register-form__header">
      <p>Identité, boutique, connexion, plan, puis récapitulatif.</p>
    </div>

    <span class="register-form__badge">{{ betaPlanNotice }}</span>

    <Stepper v-model:value="step" linear class="stepper-animated">
      <StepList>
        <Step value="1">Identité</Step>
        <Step value="2">Boutique</Step>
        <Step value="3">Connexion</Step>
        <Step value="4">Plan</Step>
        <Step value="5">Récap</Step>
      </StepList>
      <StepPanels>
        <StepPanel v-slot="{ active }" value="1">
          <Transition :name="transitionName" mode="out-in">
            <div v-if="active" key="step-1" class="register-form__panel">
              <div class="register-form__field">
                <label for="firstName">Prénom</label>
                <InputText
                  id="firstName"
                  v-model="firstName"
                  placeholder="Aïcha"
                  fluid
                  :invalid="!!errors.firstName"
                />
                <small v-if="errors.firstName" class="register-form__error">{{ errors.firstName }}</small>
              </div>

              <div class="register-form__field">
                <label for="lastName">Nom</label>
                <InputText
                  id="lastName"
                  v-model="lastName"
                  placeholder="Diallo"
                  fluid
                  :invalid="!!errors.lastName"
                />
                <small v-if="errors.lastName" class="register-form__error">{{ errors.lastName }}</small>
              </div>

              <div class="register-form__field">
                <label for="phone">Téléphone <span class="register-form__optional">(optionnel)</span></label>
                <InputText
                  id="phone"
                  v-model="phone"
                  placeholder="+223 70 00 00 00"
                  fluid
                  :invalid="!!errors.phone"
                />
                <small v-if="errors.phone" class="register-form__error">{{ errors.phone }}</small>
              </div>
            </div>
          </Transition>
        </StepPanel>

        <StepPanel v-slot="{ active }" value="2">
          <Transition :name="transitionName" mode="out-in">
            <div v-if="active" key="step-2" class="register-form__panel">
              <div class="register-form__field">
                <label for="accountName">Nom de la boutique</label>
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
                <label for="shopPhone">Téléphone boutique <span class="register-form__optional">(optionnel)</span></label>
                <InputText
                  id="shopPhone"
                  v-model="shopPhone"
                  placeholder="+223 20 00 00 00"
                  fluid
                  :invalid="!!errors.shopPhone"
                />
                <small v-if="errors.shopPhone" class="register-form__error">{{ errors.shopPhone }}</small>
              </div>

              <div class="register-form__field">
                <label for="shopAddress">Adresse <span class="register-form__optional">(optionnel)</span></label>
                <InputText
                  id="shopAddress"
                  v-model="shopAddress"
                  placeholder="Bamako, Mali"
                  fluid
                  :invalid="!!errors.shopAddress"
                />
                <small v-if="errors.shopAddress" class="register-form__error">{{ errors.shopAddress }}</small>
              </div>
            </div>
          </Transition>
        </StepPanel>

        <StepPanel v-slot="{ active }" value="3">
          <Transition :name="transitionName" mode="out-in">
            <div v-if="active" key="step-3" class="register-form__panel">
              <div class="register-form__field">
                <label for="adminEmail">E-mail</label>
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

              <div class="register-form__field">
                <label for="adminPassword">Mot de passe</label>
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

        <StepPanel v-slot="{ active }" value="4">
          <Transition :name="transitionName" mode="out-in">
            <div v-if="active" key="step-4" class="register-form__panel">
              <div class="register-form__field">
                <label for="planCode">Plan</label>
                <Select
                  id="planCode"
                  v-model="selectedPlanCode"
                  :options="planOptions"
                  option-label="label"
                  option-value="code"
                  placeholder="Choisir un plan"
                  fluid
                  :invalid="!!errors.planCode"
                />
                <small v-if="errors.planCode" class="register-form__error">{{ errors.planCode }}</small>
                <small class="register-form__optional">
                  Pendant la bêta, le serveur peut forcer le plan Starter.
                </small>
              </div>
            </div>
          </Transition>
        </StepPanel>

        <StepPanel v-slot="{ active }" value="5">
          <Transition :name="transitionName" mode="out-in">
            <div v-if="active" key="step-5" class="register-form__panel">
              <div class="register-form__recap text-sm">
                <p><strong>Client :</strong> {{ firstName }} {{ lastName }}</p>
                <p><strong>Boutique :</strong> {{ accountName }} ({{ accountSlug }})</p>
                <p><strong>E-mail :</strong> {{ adminEmail }}</p>
                <p><strong>Plan :</strong> {{ selectedPlanLabel }}</p>
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
        v-if="step !== '5'"
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
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import Select from 'primevue/select'
import Step from 'primevue/step'
import StepList from 'primevue/steplist'
import StepPanel from 'primevue/steppanel'
import StepPanels from 'primevue/steppanels'
import Stepper from 'primevue/stepper'
import { betaPlanNotice } from '@/domains/marketing/config/marketingContent'
import { useAccountSlug } from '@/domains/marketing/composables/useAccountSlug'
import { useMarketingAuth } from '@/domains/marketing/composables/useMarketingAuth'
import { plansService } from '@/domains/marketing/services/plansService'
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

const firstName = ref('')
const lastName = ref('')
const phone = ref('')
const adminEmail = ref('')
const adminPassword = ref('')
const adminPasswordConfirm = ref('')
const accountName = ref('')
const shopPhone = ref('')
const shopAddress = ref('')
const plans = ref([])
const selectedPlanCode = ref(route.query.plan ? String(route.query.plan) : 'starter')
const errors = ref({})

const { accountSlug, onSlugInput } = useAccountSlug(accountName)

const formatPlanPrice = (plan) => {
  const amount = plan.priceFcfa ?? plan.priceCents ?? 0
  if (!amount) return 'Gratuit'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    maximumFractionDigits: 0
  }).format(amount)
}

const planOptions = computed(() =>
  plans.value.map((plan) => ({
    ...plan,
    label: `${plan.name} — ${formatPlanPrice(plan)}`
  }))
)

const selectedPlanLabel = computed(() => {
  const plan = plans.value.find((p) => p.code === selectedPlanCode.value)
  return plan ? `${plan.name} (${formatPlanPrice(plan)})` : selectedPlanCode.value
})

const validateIdentityStep = () => {
  const nextErrors = {}
  if (!firstName.value.trim()) nextErrors.firstName = 'Le prénom est requis.'
  if (!lastName.value.trim()) nextErrors.lastName = 'Le nom est requis.'
  errors.value = nextErrors
  return Object.keys(nextErrors).length === 0
}

const validateShopStep = () => {
  const nextErrors = {}
  if (!accountName.value.trim()) nextErrors.accountName = 'Le nom de la boutique est requis.'
  if (!accountSlug.value.trim()) nextErrors.accountSlug = 'Le slug est requis.'
  errors.value = nextErrors
  return Object.keys(nextErrors).length === 0
}

const validateCredentialsStep = () => {
  const nextErrors = {}
  if (!adminEmail.value.trim()) nextErrors.adminEmail = 'L’e-mail est requis.'
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

const validatePlanStep = () => {
  const nextErrors = {}
  if (!selectedPlanCode.value) nextErrors.planCode = 'Choisissez un plan.'
  errors.value = nextErrors
  return Object.keys(nextErrors).length === 0
}

const prevStep = () => {
  stepDirection.value = 'back'
  const current = Number(step.value)
  if (current > 1) step.value = String(current - 1)
  errors.value = {}
}

const nextStep = () => {
  if (step.value === '1' && !validateIdentityStep()) return
  if (step.value === '2' && !validateShopStep()) return
  if (step.value === '3' && !validateCredentialsStep()) return
  if (step.value === '4' && !validatePlanStep()) return
  errors.value = {}
  stepDirection.value = 'forward'
  step.value = String(Number(step.value) + 1)
}

const { pending: loading, run: submit } = useAsyncAction(async () => {
  if (!validatePlanStep()) return
  errors.value = {}

  try {
    const result = await signupService.signup({
      firstName: firstName.value.trim(),
      lastName: lastName.value.trim(),
      phone: phone.value.trim() || undefined,
      adminEmail: adminEmail.value.trim(),
      adminPassword: adminPassword.value,
      accountName: accountName.value.trim(),
      accountSlug: accountSlug.value.trim(),
      shopPhone: shopPhone.value.trim() || undefined,
      shopAddress: shopAddress.value.trim() || undefined,
      billingEmail: adminEmail.value.trim(),
      requestedPlanCode: selectedPlanCode.value
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

onMounted(async () => {
  try {
    plans.value = await plansService.list()
    if (!plans.value.some((p) => p.code === selectedPlanCode.value)) {
      selectedPlanCode.value = plans.value[0]?.code || 'starter'
    }
  } catch {
    plans.value = [{ code: 'starter', name: 'Starter', priceFcfa: 0 }]
  }
})
</script>

<style scoped>
.register-form__header p,
.register-form__footer {
  color: var(--mkt-text-muted);
  font-size: 0.875rem;
}

.register-form__header p {
  margin: 0;
}

.register-form__badge {
  align-self: flex-start;
  max-width: 100%;
  padding: 0.25rem 0.625rem;
  border-radius: var(--mkt-radius-sm);
  background: var(--mkt-primary-soft);
  color: var(--mkt-primary);
  font-size: 0.8125rem;
  font-weight: 600;
  line-height: 1.35;
}

.register-form__optional {
  font-weight: 500;
  color: var(--mkt-text-muted);
}

.register-form__panel {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding-top: 0.5rem;
}

.register-form__recap p {
  margin: 0 0 0.5rem;
  color: var(--mkt-text);
}

.register-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  min-width: 0;
}

.register-form__field label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--mkt-text);
}

.register-form__error {
  color: #dc2626;
  font-size: 0.82rem;
}

.register-form__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.75rem;
}

.register-form__footer a {
  color: var(--mkt-primary);
  font-weight: 600;
}

.register-form :deep(.stepper-animated),
.register-form :deep(.p-stepper) {
  min-width: 0;
  width: 100%;
}

.register-form :deep(.p-steplist) {
  gap: 0.15rem;
  overflow: hidden;
}

.register-form :deep(.p-step) {
  min-width: 0;
  gap: 0.35rem;
  padding: 0.25rem 0;
}

.register-form :deep(.p-step-header) {
  gap: 0.45rem;
  min-width: 0;
}

.register-form :deep(.p-step-number) {
  --step-size: 1.2rem;
  flex-shrink: 0;
  min-width: var(--step-size);
  width: var(--step-size);
  height: var(--step-size);
  font-size: 0.68rem;
  line-height: 1;
  border-width: 1.5px;
  box-shadow: none;
  transition:
    min-width 320ms cubic-bezier(0.22, 1, 0.36, 1),
    width 320ms cubic-bezier(0.22, 1, 0.36, 1),
    height 320ms cubic-bezier(0.22, 1, 0.36, 1),
    font-size 280ms ease,
    background-color 280ms ease,
    border-color 280ms ease,
    color 280ms ease;
}

.register-form :deep(.p-step-number::after) {
  box-shadow: none;
}

.register-form :deep(.p-step-active .p-step-number) {
  --step-size: 1.75rem;
  font-size: 0.8125rem;
  border-width: 2px;
  background: var(--mkt-primary);
  border-color: var(--mkt-primary);
  color: #ffffff;
}

.register-form :deep(.p-step-header:disabled),
.register-form :deep(.p-step.p-disabled .p-step-header) {
  cursor: default;
  opacity: 1;
}

.register-form :deep(.p-step:has(~ .p-step-active) .p-step-number) {
  background: var(--mkt-primary-soft);
  border-color: var(--mkt-primary);
  color: var(--mkt-primary-strong);
}

.register-form :deep(.p-step-title) {
  max-width: 0;
  opacity: 0;
  margin: 0;
  overflow: hidden;
  white-space: nowrap;
  pointer-events: none;
  transform: translateX(-4px);
  transition:
    max-width 320ms cubic-bezier(0.22, 1, 0.36, 1),
    opacity 240ms ease,
    transform 320ms cubic-bezier(0.22, 1, 0.36, 1),
    color 240ms ease;
}

.register-form :deep(.p-step-active .p-step-title) {
  max-width: 7.5rem;
  opacity: 1;
  pointer-events: auto;
  transform: translateX(0);
  font-weight: 700;
}

.register-form :deep(.p-stepper-separator) {
  min-width: 0.75rem;
  align-self: center;
}

.register-form :deep(.p-steppanels) {
  overflow: hidden;
  min-height: 14rem;
  padding: 0.5rem 0 0;
  background: transparent;
}

.register-form :deep(.p-steppanel) {
  background: transparent;
  color: var(--mkt-text);
}

.register-form :deep(.p-inputtext),
.register-form :deep(.p-password-input) {
  background: #ffffff;
  color: var(--mkt-text);
  border-color: #cbd5e1;
  box-shadow: none;
}

.register-form :deep(.p-inputtext:enabled:focus),
.register-form :deep(.p-inputtext:enabled:hover),
.register-form :deep(.p-password-input:enabled:focus),
.register-form :deep(.p-password-input:enabled:hover) {
  box-shadow: none;
}

.register-form :deep(.p-inputtext::placeholder),
.register-form :deep(.p-password-input::placeholder) {
  color: #94a3b8;
}

.register-form :deep(.p-password .p-password-toggle-mask-icon) {
  color: #94a3b8;
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

@media (max-width: 400px) {
  .register-form {
    padding: 0;
    gap: 0.75rem;
  }

  .register-form__header p,
  .register-form__footer {
    font-size: 0.8125rem;
  }

  .register-form__badge {
    font-size: 0.7rem;
    padding: 0.28rem 0.55rem;
  }

  .register-form__panel {
    gap: 0.8rem;
    padding-top: 0.25rem;
  }

  .register-form__field {
    gap: 0.35rem;
  }

  .register-form__field label {
    font-size: 0.8rem;
  }

  .register-form__error {
    font-size: 0.75rem;
  }

  .register-form :deep(.p-step-number) {
    --step-size: 1.05rem;
    font-size: 0.62rem;
  }

  .register-form :deep(.p-step-active .p-step-number) {
    --step-size: 1.55rem;
    font-size: 0.78rem;
  }

  .register-form :deep(.p-step-active .p-step-title) {
    max-width: 5rem;
    font-size: 0.8rem;
  }

  .register-form :deep(.p-steppanels) {
    min-height: 11.5rem;
  }

  .register-form :deep(.p-inputtext),
  .register-form :deep(.p-password-input) {
    font-size: 0.88rem;
    padding-block: 0.55rem;
  }

  .register-form__actions {
    justify-content: stretch;
    gap: 0.55rem;
  }

  .register-form__actions :deep(.p-button) {
    flex: 1 1 auto;
    font-size: 0.85rem;
    padding: 0.65rem 0.85rem;
  }
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

  .register-form :deep(.p-step-number),
  .register-form :deep(.p-step-title) {
    transition: none;
  }
}
</style>
