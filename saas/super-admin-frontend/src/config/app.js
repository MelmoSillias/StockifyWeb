const resolveAccessToken = (payload) => payload?.access_token || payload?.token || null

export const appConfig = {
  app: {
    id: 'stockify-super-admin',
    name: 'Stockify Super Admin',
    title: 'Stockify Super Admin',
    subtitle: 'Console super-admin de la plateforme',
    description: 'Application dediee au pilotage de la plateforme Stockify.'
  },
  branding: {
    name: 'Stockify Super Admin',
    shortName: 'SSA',
    tagline: 'Gestion plateforme et monitoring',
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
        key: 'accounts',
        label: 'Comptes',
        icon: 'pi pi-building',
        routeName: 'accounts'
      },
      {
        key: 'shops',
        label: 'Boutiques',
        icon: 'pi pi-shop',
        routeName: 'shops'
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
    layoutPreferencesKey: 'stockify-super-admin-layout-preferences',
    authTokenKey: 'stockify-super-admin-access-token',
    authUserKey: 'stockify-super-admin-auth-user'
  },
  axios: {
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    timeout: 15000,
    withCredentials: true,
    defaultHeaders: {}
  },
  routes: {
    homeRouteName: 'home',
    loginRouteName: 'login'
  }
}
