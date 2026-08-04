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
          layoutKey: 'home',
          permission: 'dashboard.view'
        }
      },
      {
        path: 'analytics',
        name: 'analytics',
        component: () => import('@/domains/analytics/views/AnalyticsView.vue'),
        meta: {
          title: 'Analytics',
          section: 'Application',
          layoutKey: 'analytics',
          permission: 'analytics.view'
        }
      },
      {
        path: 'clients',
        name: 'clients',
        component: () => import('@/domains/client/views/ClientsView.vue'),
        meta: {
          title: 'Clientèle',
          section: 'Clientèle',
          layoutKey: 'clients',
          permission: 'client.clients.view'
        }
      },
      {
        path: 'clients/:id/journal',
        name: 'client-journal',
        component: () => import('@/domains/client/views/ClientJournalView.vue'),
        meta: {
          title: 'Journal client',
          section: 'Clientèle',
          layoutKey: 'client-journal',
          permission: 'client.journal.view'
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
          permission: 'catalog.categories.view'
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
          permission: 'catalog.products.view'
        }
      },
      {
        path: 'inventory/movements',
        name: 'inventory-movements',
        component: () => import('@/domains/inventory/views/MovementsView.vue'),
        meta: {
          title: 'Mouvements',
          section: 'Catalogue',
          layoutKey: 'inventory-movements',
          permission: 'inventory.movements.view'
        }
      },
      {
        path: 'commerce/cart',
        name: 'commerce-cart',
        component: () => import('@/domains/commerce/views/CartView.vue'),
        meta: {
          title: 'Panier',
          section: 'Commerce',
          layoutKey: 'commerce-cart',
          permission: 'commerce.cart.use'
        }
      },
      {
        path: 'commerce/orders',
        name: 'commerce-orders',
        component: () => import('@/domains/commerce/views/OrdersView.vue'),
        meta: {
          title: 'Commandes',
          section: 'Commerce',
          layoutKey: 'commerce-orders',
          permission: 'commerce.commandes.view'
        }
      },
      {
        path: 'commerce/sales',
        name: 'commerce-sales',
        component: () => import('@/domains/commerce/views/SalesView.vue'),
        meta: {
          title: 'Ventes',
          section: 'Commerce',
          layoutKey: 'commerce-sales',
          permission: 'commerce.ventes.view'
        }
      },
      {
        path: 'commerce/payments',
        name: 'commerce-payments',
        component: () => import('@/domains/commerce/views/PaymentsView.vue'),
        meta: {
          title: 'Paiements',
          section: 'Commerce',
          layoutKey: 'commerce-payments',
          permission: 'paiement.paiements.view'
        }
      },
      {
        path: 'commerce/creances',
        name: 'commerce-creances',
        component: () => import('@/domains/commerce/views/CreancesView.vue'),
        meta: {
          title: 'Carnet de dettes',
          section: 'Commerce',
          layoutKey: 'commerce-creances',
          permission: 'client.creances.view'
        }
      },
      {
        path: 'finances',
        name: 'finance',
        component: () => import('@/domains/finance/views/FinancesView.vue'),
        meta: {
          title: 'Finances',
          section: 'Finances',
          layoutKey: 'finance',
          permission: 'finance.view'
        }
      },
      {
        path: 'fournisseurs',
        name: 'fournisseurs',
        component: () => import('@/domains/fournisseur/views/FournisseursView.vue'),
        meta: {
          title: 'Fournisseurs',
          section: 'Fournisseurs',
          layoutKey: 'fournisseurs',
          permission: 'fournisseur.view'
        }
      },
      {
        path: 'fournisseurs/dettes',
        name: 'fournisseur-dettes',
        component: () => import('@/domains/fournisseur/views/DettesView.vue'),
        meta: {
          title: 'Carnet de dettes fournisseurs',
          section: 'Fournisseurs',
          layoutKey: 'fournisseur-dettes',
          permission: 'fournisseur.dettes.view'
        }
      },
      {
        path: 'fournisseurs/:id/journal',
        name: 'fournisseur-journal',
        component: () => import('@/domains/fournisseur/views/FournisseurJournalView.vue'),
        meta: {
          title: 'Journal fournisseur',
          section: 'Fournisseurs',
          layoutKey: 'fournisseur-journal',
          permission: 'fournisseur.view'
        }
      },
      {
        path: 'access/users',
        name: 'access-users',
        component: () => import('@/domains/access/views/UsersView.vue'),
        meta: {
          title: 'Utilisateurs',
          section: 'Accès & Audit',
          layoutKey: 'access-users',
          permission: 'access.users.view'
        }
      },
      {
        path: 'access/roles',
        name: 'access-roles',
        component: () => import('@/domains/access/views/RolesView.vue'),
        meta: {
          title: 'Rôles',
          section: 'Accès & Audit',
          layoutKey: 'access-roles',
          permission: 'access.roles.view'
        }
      },
      {
        path: 'access/audit',
        name: 'access-audit',
        component: () => import('@/domains/access/views/AuditLogView.vue'),
        meta: {
          title: 'Journal d\'audit',
          section: 'Accès & Audit',
          layoutKey: 'access-audit',
          permission: 'access.audit.view'
        }
      },
      {
        path: 'profil',
        name: 'profile',
        component: () => import('@/domains/auth/views/ProfileView.vue'),
        meta: {
          title: 'Mon profil',
          section: 'Application',
          layoutKey: 'profile'
        }
      },
      {
        path: 'parametres',
        name: 'parametres',
        component: () => import('@/domains/layout/views/ParametresView.vue'),
        meta: {
          title: 'Paramètres',
          section: 'Application',
          layoutKey: 'parametres'
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

  if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
    return { name: routeConfig.homeRouteName }
  }

  return true
})

export default router
