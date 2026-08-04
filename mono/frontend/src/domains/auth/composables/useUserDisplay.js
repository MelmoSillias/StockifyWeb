import { computed, unref } from 'vue'

import { appConfig } from '@/config/app'

export function useUserDisplay(userSource) {
  const user = computed(() => unref(userSource))

  const displayName = computed(() => {
    const currentUser = user.value
    if (!currentUser) {
      return appConfig.branding.name
    }

    const fullName = [currentUser.first_name, currentUser.last_name].filter(Boolean).join(' ').trim()
    return fullName || currentUser.email || currentUser.username || appConfig.branding.name
  })

  const userInitials = computed(() => {
    return displayName.value
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((value) => value[0]?.toUpperCase())
      .join('')
  })

  return {
    user,
    displayName,
    userInitials
  }
}

export function formatUserStatus(status) {
  const labels = {
    active: 'Actif',
    pending: 'En attente',
    suspended: 'Suspendu'
  }

  return labels[status] || status
}

export function userStatusSeverity(status) {
  if (status === 'active') {
    return 'success'
  }

  if (status === 'pending') {
    return 'warn'
  }

  return 'danger'
}

export function formatProfileDate(value) {
  if (!value) {
    return '—'
  }

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(new Date(value))
}

export function truncateUserAgent(userAgent, maxLength = 48) {
  if (!userAgent) {
    return '—'
  }

  if (userAgent.length <= maxLength) {
    return userAgent
  }

  return `${userAgent.slice(0, maxLength - 1)}…`
}
