import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/domains/auth/stores/auth'
import { appConfig } from '@/config/app'

export function useMarketingAuth() {
  const authStore = useAuthStore()
  const router = useRouter()
  const { isAuthenticated } = storeToRefs(authStore)
  const { homeRouteName, registerRouteName, loginRouteName, landingRouteName } = appConfig.routes

  const dashboardTo = { name: homeRouteName }
  const registerTo = { name: registerRouteName }
  const loginTo = { name: loginRouteName }
  const landingTo = { name: landingRouteName }

  const anchorTo = (section) => ({
    name: landingRouteName,
    hash: section.startsWith('#') ? section : `#${section}`
  })

  const primaryAction = computed(() =>
    isAuthenticated.value
      ? { to: dashboardTo, label: 'Accéder au dashboard' }
      : { to: registerTo, label: 'Créer un compte' }
  )

  const signupTo = (query = {}) =>
    computed(() =>
      isAuthenticated.value
        ? dashboardTo
        : { name: registerRouteName, query }
    )

  const logout = async () => {
    if (!appConfig.auth.enabled) {
      return
    }

    authStore.logout()
    await router.push({ name: landingRouteName })
  }

  return {
    isAuthenticated,
    dashboardTo,
    registerTo,
    loginTo,
    landingTo,
    anchorTo,
    primaryAction,
    signupTo,
    logout
  }
}
