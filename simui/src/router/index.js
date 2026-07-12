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
    meta: { requiresAuth: appConfig.auth.enabled },
    children: [
      {
        path: '',
        redirect: { name: 'home' }
      },
      {
        path: 'accueil',
        name: 'home',
        component: () => import('@/domains/home/views/HomeView.vue'),
        meta: {
          title: 'Accueil',
          section: 'SimUI',
          layoutKey: 'home'
        }
      },
      {
        path: 'documentation',
        name: 'docs',
        component: () => import('@/domains/docs/views/DocumentationView.vue'),
        meta: {
          title: 'Documentation',
          section: 'SimUI',
          layoutKey: 'docs'
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

  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return { name: routeConfig.homeRouteName }
  }

  return true
})

export default router
