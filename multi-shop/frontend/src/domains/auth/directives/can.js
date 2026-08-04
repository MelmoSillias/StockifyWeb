import { useAuthStore } from '@/domains/auth/stores/auth'

const resolveBinding = (binding) => {
  if (!binding) {
    return null
  }

  if (typeof binding === 'string') {
    return binding
  }

  if (Array.isArray(binding)) {
    return binding
  }

  if (typeof binding === 'object' && binding.value) {
    return binding.value
  }

  return null
}

const hasAccess = (authStore, binding) => {
  const value = resolveBinding(binding)

  if (!value) {
    return true
  }

  if (Array.isArray(value)) {
    return authStore.hasAnyPermission(...value)
  }

  return authStore.hasPermission(value)
}

const applyVisibility = (el, binding) => {
  const authStore = useAuthStore()
  const allowed = hasAccess(authStore, binding?.value)

  el.style.display = allowed ? '' : 'none'
}

export const canDirective = {
  mounted(el, binding) {
    applyVisibility(el, binding)
  },
  updated(el, binding) {
    applyVisibility(el, binding)
  }
}

export default canDirective
