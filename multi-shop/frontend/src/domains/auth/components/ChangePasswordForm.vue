<script setup>
import { reactive, ref } from 'vue'

import Button from 'primevue/button'
import Password from 'primevue/password'

import { profileService } from '@/domains/auth/services/profileService'
import { useEntityActions } from '@/domains/shared/composables/useEntityActions'
import { useAsyncAction } from '@/domains/shared/composables/useAsyncAction'

const { showError, showSuccess } = useEntityActions()

const errors = reactive({
  current_password: '',
  new_password: '',
  confirm_password: ''
})

const form = reactive({
  current_password: '',
  new_password: '',
  confirm_password: ''
})

const clearErrors = () => {
  errors.current_password = ''
  errors.new_password = ''
  errors.confirm_password = ''
}

const resetForm = () => {
  form.current_password = ''
  form.new_password = ''
  form.confirm_password = ''
}

const validate = () => {
  clearErrors()
  let valid = true

  if (!form.current_password) {
    errors.current_password = 'Le mot de passe actuel est requis.'
    valid = false
  }

  if (!form.new_password) {
    errors.new_password = 'Le nouveau mot de passe est requis.'
    valid = false
  } else if (form.new_password.length < 8) {
    errors.new_password = 'Le mot de passe doit contenir au moins 8 caractères.'
    valid = false
  }

  if (!form.confirm_password) {
    errors.confirm_password = 'La confirmation est requise.'
    valid = false
  } else if (form.new_password !== form.confirm_password) {
    errors.confirm_password = 'Les mots de passe ne correspondent pas.'
    valid = false
  }

  return valid
}

const { pending: saving, run: save } = useAsyncAction(async () => {
  if (!validate()) {
    return
  }

  try {
    await profileService.changePassword({
      current_password: form.current_password,
      new_password: form.new_password
    })
    showSuccess('Mot de passe mis à jour.')
    resetForm()
  } catch (error) {
    showError(error?.response?.data?.error || error?.message || 'Impossible de mettre à jour le mot de passe.')
  }
})
</script>

<template>
  <form class="change-password-form" @submit.prevent="save">
    <div class="change-password-form__intro">
      <h2>Sécurité du compte</h2>
      <p>Choisissez un mot de passe robuste d'au moins 8 caractères.</p>
    </div>

    <section class="change-password-form__section">
      <div class="change-password-form__grid">
        <label class="change-password-form__field change-password-form__field--full">
          <span>Mot de passe actuel</span>
          <Password
            v-model="form.current_password"
            class="w-full"
            toggle-mask
            :feedback="false"
            :invalid="!!errors.current_password"
            fluid
            @update:model-value="errors.current_password = ''"
          />
          <small v-if="errors.current_password" class="change-password-form__error">
            {{ errors.current_password }}
          </small>
        </label>
        <label class="change-password-form__field">
          <span>Nouveau mot de passe</span>
          <Password
            v-model="form.new_password"
            class="w-full"
            toggle-mask
            :feedback="false"
            :invalid="!!errors.new_password"
            fluid
            @update:model-value="errors.new_password = ''"
          />
          <small v-if="errors.new_password" class="change-password-form__error">
            {{ errors.new_password }}
          </small>
        </label>
        <label class="change-password-form__field">
          <span>Confirmation</span>
          <Password
            v-model="form.confirm_password"
            class="w-full"
            toggle-mask
            :feedback="false"
            :invalid="!!errors.confirm_password"
            fluid
            @update:model-value="errors.confirm_password = ''"
          />
          <small v-if="errors.confirm_password" class="change-password-form__error">
            {{ errors.confirm_password }}
          </small>
        </label>
      </div>
    </section>

    <div class="change-password-form__actions">
      <Button
        type="submit"
        label="Mettre à jour le mot de passe"
        icon="pi pi-lock"
        :loading="saving"
        :disabled="saving"
      />
    </div>
  </form>
</template>

<style scoped>
.change-password-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  min-width: 0;
}

.change-password-form__intro h2 {
  margin: 0 0 0.25rem;
  font-size: 1.125rem;
}

.change-password-form__intro p {
  margin: 0;
  color: var(--text-color-secondary, #64748b);
  font-size: 0.875rem;
}

.change-password-form__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem 1rem;
}

.change-password-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 0;
}

.change-password-form__field--full {
  grid-column: 1 / -1;
}

.change-password-form__field > span {
  font-size: 0.875rem;
  color: var(--text-color-secondary, #64748b);
}

.change-password-form__error {
  color: var(--p-red-500, #ef4444);
  font-size: 0.8125rem;
}

.change-password-form__actions {
  display: flex;
  justify-content: flex-end;
}

@media (max-width: 767px) {
  .change-password-form__grid {
    grid-template-columns: 1fr;
  }

  .change-password-form__actions {
    justify-content: stretch;
  }

  .change-password-form__actions :deep(.p-button) {
    width: 100%;
  }
}

@media (max-width: 360px) {
  .change-password-form {
    gap: 1rem;
  }
}
</style>
