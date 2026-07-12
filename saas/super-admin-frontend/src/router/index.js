import { createRouter, createWebHistory } from 'vue-router'
import { appConfig } from '@/config/app'
import { useAuthStore } from '@/domains/auth/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/domains/auth/views/LoginView.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/',
    component: () => import('@/domains/layout/components/AppShell.vue'),
    meta: { requiresAuth: appConfig.auth.enabled, requiresSuperAdmin: true },
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('@/domains/platform/views/DashboardView.vue'),
        meta: {
          title: 'Dashboard',
          section: 'Plateforme',
          layoutKey: 'home'
        }
      },
      {
        path: 'accounts',
        name: 'accounts',
        component: () => import('@/domains/platform/views/AccountsListView.vue'),
        meta: {
          title: 'Comptes',
          section: 'Plateforme',
          layoutKey: 'accounts'
        }
      },
      {
        path: 'accounts/:id',
        name: 'account-detail',
        component: () => import('@/domains/platform/views/AccountDetailView.vue'),
        meta: {
          title: 'Détail compte',
          section: 'Plateforme',
          layoutKey: 'account-detail'
        }
      },
      {
        path: 'shops',
        name: 'shops',
        component: () => import('@/domains/platform/views/ShopsListView.vue'),
        meta: {
          title: 'Boutiques',
          section: 'Plateforme',
          layoutKey: 'shops'
        }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to) => {
  const authStore = useAuthStore()
  const { auth, routes: routeConfig } = appConfig

  if (!auth.enabled) {
    return true
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return {
      name: routeConfig.loginRouteName,
      query: { redirect: to.fullPath }
    }
  }

  const requiresSuperAdmin = to.matched.some((record) => record.meta?.requiresSuperAdmin)
  if (requiresSuperAdmin) {
    const roles = authStore.user?.roles || []
    if (!roles.includes('ROLE_SUPER_ADMIN')) {
      authStore.logout()
      return {
        name: routeConfig.loginRouteName,
        query: { redirect: to.fullPath }
      }
    }
  }

  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return { name: routeConfig.homeRouteName }
  }

  return true
})

export default router
