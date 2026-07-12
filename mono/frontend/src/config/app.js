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
        routeName: 'home'
      },
      {
        key: 'catalog',
        label: 'Catalogue',
        icon: 'pi pi-book',
        items: [
          {
            key: 'catalog-products',
            label: 'Produits',
            icon: 'pi pi-tag',
            routeName: 'catalog-products',
            section: 'Catalogue'
          },
          {
            key: 'catalog-categories',
            label: 'Catégories',
            icon: 'pi pi-sitemap',
            routeName: 'catalog-categories',
            section: 'Catalogue'
          },
          {
            key: 'inventory-movements',
            label: 'Mouvements',
            icon: 'pi pi-history',
            routeName: 'inventory-movements',
            section: 'Catalogue'
          }
        ]
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
    authUserKey: 'stockify-auth-user'
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
