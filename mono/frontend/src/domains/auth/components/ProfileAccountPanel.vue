<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'

import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'

import { useAuthStore } from '@/domains/auth/stores/auth'
import {
  formatProfileDate,
  formatUserStatus,
  useUserDisplay,
  userStatusSeverity
} from '@/domains/auth/composables/useUserDisplay'

const authStore = useAuthStore()
const router = useRouter()
const { user } = storeToRefs(authStore)
const { displayName } = useUserDisplay(user)

const roles = computed(() => user.value?.roles || [])
</script>

<template>
  <div class="profile-account-panel">
    <div class="profile-account-panel__intro">
      <h2>Informations du compte</h2>
      <p>Vos coordonnées et attributions sont gérées par l'administration.</p>
    </div>

    <section class="profile-account-panel__section">
      <h3>Identité</h3>
      <div class="profile-account-panel__grid">
        <label class="profile-account-panel__field">
          <span>Prénom</span>
          <InputText :model-value="user?.first_name || '—'" disabled fluid />
        </label>
        <label class="profile-account-panel__field">
          <span>Nom</span>
          <InputText :model-value="user?.last_name || '—'" disabled fluid />
        </label>
        <label class="profile-account-panel__field">
          <span>Email</span>
          <InputText :model-value="user?.email || '—'" disabled fluid />
        </label>
        <label class="profile-account-panel__field">
          <span>Identifiant</span>
          <InputText :model-value="user?.username || '—'" disabled fluid />
        </label>
        <label class="profile-account-panel__field profile-account-panel__field--full">
          <span>Nom affiché</span>
          <InputText :model-value="displayName" disabled fluid />
        </label>
      </div>
    </section>

    <section class="profile-account-panel__section">
      <h3>Statut et rôles</h3>
      <div class="profile-account-panel__grid">
        <div class="profile-account-panel__field">
          <span>Statut</span>
          <div class="profile-account-panel__tags">
            <Tag
              :value="formatUserStatus(user?.status)"
              :severity="userStatusSeverity(user?.status)"
              rounded
            />
          </div>
        </div>
        <div class="profile-account-panel__field">
          <span>Rôles</span>
          <div class="profile-account-panel__tags">
            <Tag
              v-for="role in roles"
              :key="role"
              :value="role"
              severity="secondary"
              rounded
            />
            <span v-if="roles.length === 0" class="profile-account-panel__empty">—</span>
          </div>
        </div>
        <label class="profile-account-panel__field">
          <span>Membre depuis</span>
          <InputText :model-value="formatProfileDate(user?.created_at)" disabled fluid />
        </label>
        <label class="profile-account-panel__field">
          <span>Dernière connexion</span>
          <InputText :model-value="formatProfileDate(user?.last_login_at)" disabled fluid />
        </label>
      </div>
    </section>

    <div class="profile-account-panel__footer">
      <Button
        label="Préférences d'interface"
        icon="pi pi-sparkles"
        text
        @click="router.push({ name: 'parametres' })"
      />
    </div>
  </div>
</template>

<style scoped>
.profile-account-panel {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  min-width: 0;
}

.profile-account-panel__intro h2 {
  margin: 0 0 0.25rem;
  font-size: 1.125rem;
}

.profile-account-panel__intro p {
  margin: 0;
  color: var(--text-color-secondary, #64748b);
  font-size: 0.875rem;
}

.profile-account-panel__section h3 {
  margin: 0 0 0.75rem;
  font-size: 1rem;
}

.profile-account-panel__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem 1rem;
}

.profile-account-panel__field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 0;
}

.profile-account-panel__field--full {
  grid-column: 1 / -1;
}

.profile-account-panel__field > span {
  font-size: 0.875rem;
  color: var(--text-color-secondary, #64748b);
}

.profile-account-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  min-height: 2.25rem;
  align-items: center;
}

.profile-account-panel__empty {
  color: var(--text-color-secondary, #64748b);
}

.profile-account-panel__footer {
  display: flex;
  justify-content: flex-start;
}

@media (max-width: 767px) {
  .profile-account-panel__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 360px) {
  .profile-account-panel {
    gap: 1rem;
  }
}
</style>
