import { createRouter, createWebHistory } from 'vue-router'
import { appConfig } from '@/config/app'
import { useAuthStore } from '@/domains/auth/stores/auth'

const routes = [
  {
    path: '/',
    name: 'landing',
    component: () => import('@/domains/home/views/LandingView.vue')
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/domains/auth/views/LoginView.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/app',
    component: () => import('@/domains/layout/components/AppShell.vue'),
    meta: { requiresAuth: appConfig.auth.enabled },
    children: [
      {
        path: '',
        redirect: { name: 'home' }
      },
      {
        path: 'dashboard',
        name: 'home',
        component: () => import('@/domains/home/views/HomeView.vue'),
        meta: {
          title: 'Dashboard',
          section: 'Application',
          layoutKey: 'home'
        }
      },
      {
        path: 'catalog/categories',
        name: 'catalog-categories',
        component: () => import('@/domains/catalog/views/CategoriesView.vue'),
        meta: {
          title: 'Catégories',
          section: 'Catalogue',
          layoutKey: 'catalog-categories'
        }
      },
      {
        path: 'catalog/products',
        name: 'catalog-products',
        component: () => import('@/domains/catalog/views/ProductsView.vue'),
        meta: {
          title: 'Produits',
          section: 'Catalogue',
          layoutKey: 'catalog-products'
        }
      },
      {
        path: 'inventory/movements',
        name: 'inventory-movements',
        component: () => import('@/domains/inventory/views/MovementsView.vue'),
        meta: {
          title: 'Mouvements',
          section: 'Catalogue',
          layoutKey: 'inventory-movements'
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
