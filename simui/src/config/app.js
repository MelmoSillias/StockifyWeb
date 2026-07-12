const resolveAccessToken = (payload) => payload?.access_token || payload?.token || null

export const appConfig = {
  app: {
    id: 'simui',
    name: 'SimUI',
    title: 'SimUI',
    subtitle: 'Base frontend Vue et PrimeVue',
    description: 'Modele reutilisable pour lancer rapidement un frontend metier.'
  },
  branding: {
    name: 'SimUI',
    shortName: 'SU',
    tagline: 'Frontend foundation for business applications',
    logoUrl: null,
    supportEmail: 'hello@simui.local'
  },
  navigation: {
    variant: 'sidebar-topbar',
    sidebarMode: 'fixed',
    sidebarCollapsed: false,
    topbarSearchPlaceholder: 'Rechercher une vue ou un module',
    items: [
      {
        key: 'home',
        label: 'Accueil',
        icon: 'pi pi-home',
        routeName: 'home'
      },
      {
        key: 'docs',
        label: 'Documentation',
        icon: 'pi pi-book',
        routeName: 'docs'
      }
    ]
  },
  auth: {
    enabled: false,
    mode: 'jwt',
    tokenType: 'Bearer',
    loginEndpoint: '/login_check',
    meEndpoint: '/auth/me',
    redirectOn401: true,
    tokenResolver: resolveAccessToken
  },
  storage: {
    layoutPreferencesKey: 'simui-layout-preferences',
    authTokenKey: 'simui-access-token',
    authUserKey: 'simui-auth-user'
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
