const resolveAccessToken = (payload) => payload?.access_token || payload?.token || null

export const appConfig = {
  app: {
    id: 'stockify-user',
    name: 'Stockify',
    title: 'Stockify',
    subtitle: 'Gestion boutique, catalogue et stock',
    description: 'Application metier Stockify pour les utilisateurs de compte.'
  },
  branding: {
    name: 'Stockify',
    shortName: 'ST',
    tagline: 'Pilotez votre boutique en temps reel',
    logoUrl: null,
    supportEmail: 'support@stockify.local'
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
        routeName: 'home'
      },
      {
        key: 'catalog-categories',
        label: 'Catégories',
        icon: 'pi pi-sitemap',
        routeName: 'catalog-categories',
        section: 'Catalogue'
      },
      {
        key: 'catalog-products',
        label: 'Produits',
        icon: 'pi pi-tag',
        routeName: 'catalog-products',
        section: 'Catalogue'
      },
      {
        key: 'catalog-variants',
        label: 'Variantes',
        icon: 'pi pi-barcode',
        routeName: 'catalog-variants',
        section: 'Catalogue'
      },
      {
        key: 'inventory-lots',
        label: 'Lots',
        icon: 'pi pi-inbox',
        routeName: 'inventory-lots',
        section: 'Stock'
      },
      {
        key: 'inventory-movements',
        label: 'Mouvements',
        icon: 'pi pi-history',
        routeName: 'inventory-movements',
        section: 'Stock'
      },
      {
        key: 'inventory-alerts',
        label: 'Alertes',
        icon: 'pi pi-exclamation-triangle',
        routeName: 'inventory-alerts',
        section: 'Stock'
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
    layoutPreferencesKey: 'stockify-user-layout-preferences',
    authTokenKey: 'stockify-user-access-token',
    authUserKey: 'stockify-user-auth-user',
    tenantSelectionKey: 'stockify-user-tenant-selection'
  },
  axios: {
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
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
