import { createRouter, createWebHistory } from 'vue-router'
import { appConfig } from '@/config/app'
import { useAuthStore } from '@/domains/auth/stores/auth'
import { useTenantStore } from '@/domains/tenancy/stores/tenant'

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
          layoutKey: 'catalog-categories',
          requiresTenant: true
        }
      },
      {
        path: 'catalog/products',
        name: 'catalog-products',
        component: () => import('@/domains/catalog/views/ProductsView.vue'),
        meta: {
          title: 'Produits',
          section: 'Catalogue',
          layoutKey: 'catalog-products',
          requiresTenant: true
        }
      },
      {
        path: 'catalog/variants',
        name: 'catalog-variants',
        component: () => import('@/domains/catalog/views/VariantsView.vue'),
        meta: {
          title: 'Variantes',
          section: 'Catalogue',
          layoutKey: 'catalog-variants',
          requiresTenant: true
        }
      },
      {
        path: 'inventory/lots',
        name: 'inventory-lots',
        component: () => import('@/domains/inventory/views/LotsView.vue'),
        meta: {
          title: 'Lots',
          section: 'Stock',
          layoutKey: 'inventory-lots',
          requiresTenant: true
        }
      },
      {
        path: 'inventory/movements',
        name: 'inventory-movements',
        component: () => import('@/domains/inventory/views/MovementsView.vue'),
        meta: {
          title: 'Mouvements',
          section: 'Stock',
          layoutKey: 'inventory-movements',
          requiresTenant: true
        }
      },
      {
        path: 'inventory/alerts',
        name: 'inventory-alerts',
        component: () => import('@/domains/inventory/views/AlertsView.vue'),
        meta: {
          title: 'Alertes',
          section: 'Stock',
          layoutKey: 'inventory-alerts',
          requiresTenant: true
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
  const tenantStore = useTenantStore()
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

  const requiresTenant = to.matched.some((record) => record.meta?.requiresTenant)
  if (requiresTenant && (!tenantStore.accountId || !tenantStore.shopId)) {
    return { name: routeConfig.homeRouteName }
  }

  return true
})

export default router
