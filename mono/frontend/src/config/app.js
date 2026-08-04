import shopConfig from '@/shopConfig'

const resolveAccessToken = (payload) => payload?.access_token || payload?.token || null

const logoPath = shopConfig.brandingAssets?.logo
  ? `/${shopConfig.brandingAssets.logo}`
  : null

export const appConfig = {
  app: {
    id: `stockify-${shopConfig.id}`,
    name: shopConfig.brandName,
    title: shopConfig.appTitle,
    subtitle: shopConfig.brandSubtitle,
    description: `Application métier Stockify — ${shopConfig.displayName}.`
  },
  branding: {
    name: shopConfig.brandName,
    shortName: shopConfig.brandName.slice(0, 2).toUpperCase(),
    tagline: shopConfig.brandSubtitle,
    logoUrl: logoPath,
    supportEmail: shopConfig.printProfile?.email || 'support@stockify.local'
  },
  shop: {
    id: shopConfig.id,
    displayName: shopConfig.displayName,
    phone: shopConfig.shopPhone || null,
    printProfile: shopConfig.printProfile || null
  },
  navigation: {
    variant: 'sidebar-topbar',
    sidebarMode: 'fixed',
    sidebarCollapsed: false,
    topbarSearchPlaceholder: 'Rechercher une vue ou un module',
    items: [
      {
        key: 'home',
        label: 'Dashboard',
        icon: 'pi pi-home',
        routeName: 'home',
        requiredPermission: 'dashboard.view'
      },
      {
        key: 'analytics',
        label: 'Analytics',
        icon: 'pi pi-chart-bar',
        routeName: 'analytics',
        requiredPermission: 'analytics.view'
      },
      {
        key: 'clients',
        label: 'Clientèle',
        icon: 'pi pi-users',
        routeName: 'clients',
        requiredPermission: 'client.clients.view'
      },
      {
        key: 'catalog',
        label: 'Catalogue',
        icon: 'pi pi-inbox',
        items: [
          {
            key: 'catalog-products',
            label: 'Produits',
            icon: 'pi pi-tag',
            routeName: 'catalog-products',
            section: 'Catalogue',
            requiredPermission: 'catalog.products.view'
          },
          {
            key: 'catalog-categories',
            label: 'Catégories',
            icon: 'pi pi-sitemap',
            routeName: 'catalog-categories',
            section: 'Catalogue',
            requiredPermission: 'catalog.categories.view'
          },
          {
            key: 'inventory-movements',
            label: 'Mouvements',
            icon: 'pi pi-history',
            routeName: 'inventory-movements',
            section: 'Catalogue',
            requiredPermission: 'inventory.movements.view'
          }
        ]
      },
      {
        key: 'commerce',
        label: 'Commerce',
        icon: 'pi pi-shopping-bag',
        items: [
          {
            key: 'commerce-cart',
            label: 'Panier',
            icon: 'pi pi-shopping-cart',
            routeName: 'commerce-cart',
            section: 'Commerce',
            requiredPermission: 'commerce.cart.use'
          },
          {
            key: 'commerce-orders',
            label: 'Commandes',
            icon: 'pi pi-list',
            routeName: 'commerce-orders',
            section: 'Commerce',
            requiredPermission: 'commerce.commandes.view'
          },
          {
            key: 'commerce-sales',
            label: 'Ventes',
            icon: 'pi pi-shopping-bag',
            routeName: 'commerce-sales',
            section: 'Commerce',
            requiredPermission: 'commerce.ventes.view'
          },
          {
            key: 'commerce-payments',
            label: 'Paiements',
            icon: 'pi pi-wallet',
            routeName: 'commerce-payments',
            section: 'Commerce',
            requiredPermission: 'paiement.paiements.view'
          },
          {
            key: 'commerce-creances',
            label: 'Carnet de dettes',
            icon: 'pi pi-book',
            routeName: 'commerce-creances',
            section: 'Commerce',
            requiredPermission: 'client.creances.view'
          }
        ]
      },
      {
        key: 'finance',
        label: 'Finances',
        icon: 'pi pi-chart-line',
        routeName: 'finance',
        requiredPermission: 'finance.view'
      },
      {
        key: 'fournisseurs',
        label: 'Fournisseurs',
        icon: 'pi pi-truck',
        items: [
          {
            key: 'fournisseurs-list',
            label: 'Liste',
            icon: 'pi pi-list',
            routeName: 'fournisseurs',
            section: 'Fournisseurs',
            requiredPermission: 'fournisseur.view'
          },
          {
            key: 'fournisseur-dettes',
            label: 'Carnet de dettes',
            icon: 'pi pi-book',
            routeName: 'fournisseur-dettes',
            section: 'Fournisseurs',
            requiredPermission: 'fournisseur.dettes.view'
          }
        ]
      },
      {
        key: 'access',
        label: 'Accès & Audit',
        icon: 'pi pi-shield',
        requiredPermission: ['access.users.view', 'access.roles.view', 'access.audit.view'],
        items: [
          {
            key: 'access-users',
            label: 'Utilisateurs',
            icon: 'pi pi-users',
            routeName: 'access-users',
            section: 'Accès & Audit',
            requiredPermission: 'access.users.view'
          },
          {
            key: 'access-roles',
            label: 'Rôles',
            icon: 'pi pi-id-card',
            routeName: 'access-roles',
            section: 'Accès & Audit',
            requiredPermission: 'access.roles.view'
          },
          {
            key: 'access-audit',
            label: 'Journal d\'audit',
            icon: 'pi pi-history',
            routeName: 'access-audit',
            section: 'Accès & Audit',
            requiredPermission: 'access.audit.view'
          }
        ]
      },
      {
        key: 'parametres',
        label: 'Paramètres',
        icon: 'pi pi-cog',
        routeName: 'parametres'
      }
    ]
  },
  auth: {
    enabled: true,
    mode: 'jwt',
    tokenType: 'Bearer',
    loginEndpoint: '/login_check',
    meEndpoint: '/me',
    redirectOn401: true,
    tokenResolver: resolveAccessToken
  },
  storage: {
    layoutPreferencesKey: 'stockify-layout-preferences',
    authTokenKey: 'stockify-access-token',
    authUserKey: 'stockify-auth-user',
    authPermissionsKey: 'stockify-auth-permissions',
    commerceCartKey: 'stockify-commerce-cart'
  },
  axios: {
    baseURL: shopConfig.viteApiPrefix || 'http://localhost:8000/api',
    timeout: 15000,
    withCredentials: true,
    defaultHeaders: {}
  },
  routes: {
    landingRouteName: 'landing',
    homeRouteName: 'home',
    loginRouteName: 'login'
  }
}
