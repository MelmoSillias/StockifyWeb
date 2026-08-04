<script setup>
import { computed } from 'vue'
import { storeToRefs } from 'pinia'

import Avatar from 'primevue/avatar'
import Card from 'primevue/card'
import Tag from 'primevue/tag'

import { useAuthStore } from '@/domains/auth/stores/auth'
import {
  formatProfileDate,
  formatUserStatus,
  useUserDisplay,
  userStatusSeverity
} from '@/domains/auth/composables/useUserDisplay'

const authStore = useAuthStore()
const { user } = storeToRefs(authStore)
const { displayName, userInitials } = useUserDisplay(user)

const roles = computed(() => user.value?.roles || [])
const status = computed(() => user.value?.status || 'pending')
const lastLoginLabel = computed(() => formatProfileDate(user.value?.last_login_at))
</script>

<template>
  <Card class="dashboard-kpi-card profile-summary-card">
    <template #content>
      <div class="profile-summary-card__layout">
        <Avatar
          :image="user?.avatar"
          :label="userInitials"
          shape="circle"
          size="xlarge"
          class="profile-summary-card__avatar"
        />
        <div class="profile-summary-card__body">
          <p class="profile-summary-card__label">Compte utilisateur</p>
          <h2 class="profile-summary-card__name">{{ displayName }}</h2>
          <p class="profile-summary-card__email">{{ user?.email || '—' }}</p>
          <div class="profile-summary-card__meta">
            <Tag
              :value="formatUserStatus(status)"
              :severity="userStatusSeverity(status)"
              rounded
            />
            <Tag
              v-for="role in roles"
              :key="role"
              :value="role"
              severity="secondary"
              rounded
            />
          </div>
          <p class="profile-summary-card__hint">
            Dernière connexion : {{ lastLoginLabel }}
          </p>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
.profile-summary-card {
  min-width: 0;
}

.profile-summary-card__layout {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: clamp(0.85rem, 2vw, 1.25rem);
}

.profile-summary-card__avatar {
  flex-shrink: 0;
}

.profile-summary-card__body {
  min-width: 0;
  flex: 1;
}

.profile-summary-card__label {
  margin: 0 0 0.25rem;
  color: var(--text-color-secondary, #64748b);
  font-size: 0.8125rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.profile-summary-card__name {
  margin: 0 0 0.25rem;
  font-size: clamp(1.125rem, 2.5vw, 1.375rem);
  line-height: 1.2;
}

.profile-summary-card__email {
  margin: 0 0 0.75rem;
  color: var(--text-color-secondary, #64748b);
  font-size: 0.875rem;
  word-break: break-word;
}

.profile-summary-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.profile-summary-card__hint {
  margin: 0;
  color: var(--text-color-secondary, #64748b);
  font-size: 0.8125rem;
}

@media (max-width: 767px) {
  .profile-summary-card__layout {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .profile-summary-card__meta {
    justify-content: center;
  }
}

@media (max-width: 360px) {
  .profile-summary-card__layout {
    gap: 0.75rem;
    padding: 0.75rem;
  }
}
</style>
