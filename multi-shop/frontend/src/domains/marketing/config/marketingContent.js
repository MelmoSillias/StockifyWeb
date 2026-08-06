export const featureLabels = {
  'stockify.multi_shop': 'Multi-boutiques',
  'stockify.orders': 'Commandes',
  'stockify.quotes': 'Devis',
  'stockify.analytics': 'Analytics avancées',
  'stockify.suppliers': 'Fournisseurs'
}

export const heroPillars = [
  {
    icon: 'pi pi-box',
    title: 'Maîtrisez votre stock',
    description: 'Visualisez vos niveaux, alertes et mouvements en temps réel sur toutes vos boutiques.'
  },
  {
    icon: 'pi pi-shopping-cart',
    title: 'Vendez sans friction',
    description: 'Panier, commandes et paiements connectés pour un flux commercial fluide et rapide.'
  },
  {
    icon: 'pi pi-chart-line',
    title: 'Décidez avec clarté',
    description: 'Analytics et finances consolidées pour piloter votre activité avec confiance.'
  }
]

export const showcaseSections = [
  {
    id: 'stock',
    variant: 'light',
    reversed: false,
    eyebrow: 'Inventaire',
    title: 'Gardez une longueur d’avance sur vos ruptures.',
    description: 'Suivez chaque entrée et sortie, recevez des alertes de seuil et anticipez vos réapprovisionnements avant qu’il ne soit trop tard.',
    cta: 'Essayer LafiaSugu',
    mockup: {
      theme: 'light',
      title: 'Stock en direct',
      stats: [
        { label: 'Produits actifs', value: '1 284' },
        { label: 'Alertes', value: '7', hint: 'Seuil bas' }
      ],
      rows: ['Huile 5L — 12 restants', 'Riz 25kg — réappro demain', 'Savon — stock OK']
    }
  },
  {
    id: 'commerce',
    variant: 'accent',
    reversed: true,
    eyebrow: 'Commerce',
    title: 'Du panier à l’encaissement, tout est connecté.',
    description: 'Encaissez, suivez les ventes, gérez les créances clients et gardez une vision claire de votre chiffre en un seul endroit.',
    cta: 'Créer mon compte',
    mockup: {
      theme: 'dark',
      title: 'Ventes du jour',
      stats: [
        { label: 'Ventes', value: '48' },
        { label: 'Panier moyen', value: '12 400 F' }
      ],
      bars: [38, 62, 48, 78, 55, 88, 72]
    }
  }
]

export const heroStats = [
  { value: 'Multi', label: 'Boutiques gérées' },
  { value: '100%', label: 'Centralisé' },
  { value: '1 mois', label: 'Essai Starter' }
]

export const trustLogos = [
  { icon: 'pi pi-shop', label: 'Multi-boutique' },
  { icon: 'pi pi-inbox', label: 'Catalogue' },
  { icon: 'pi pi-shopping-cart', label: 'Commerce' },
  { icon: 'pi pi-chart-bar', label: 'Analytics' },
  { icon: 'pi pi-wallet', label: 'Finances' },
  { icon: 'pi pi-shield', label: 'Accès sécurisé' }
]

export const benefits = [
  {
    icon: 'pi pi-box',
    title: 'Stock centralisé',
    description: 'Suivez vos niveaux, alertes et mouvements en temps réel sur l’ensemble de vos points de vente.'
  },
  {
    icon: 'pi pi-shopping-cart',
    title: 'Commerce intégré',
    description: 'Panier, commandes, ventes et paiements réunis dans un flux unique, sans ressaisie.'
  },
  {
    icon: 'pi pi-shop',
    title: 'Multi-boutique natif',
    description: 'Pilotez plusieurs magasins depuis une seule interface avec des droits granulaires.'
  },
  {
    icon: 'pi pi-chart-line',
    title: 'Pilotage financier',
    description: 'Finances, créances et fournisseurs connectés pour une vision claire de votre activité.'
  }
]

export const featureModules = [
  {
    icon: 'pi pi-inbox',
    title: 'Catalogue',
    description: 'Produits, catégories et unités de mesure structurés pour un inventaire fiable.',
    span: 'wide'
  },
  {
    icon: 'pi pi-history',
    title: 'Mouvements de stock',
    description: 'Entrées, sorties et traçabilité complète de chaque variation.',
    span: 'normal'
  },
  {
    icon: 'pi pi-cart-arrow-down',
    title: 'Commerce',
    description: 'Panier, commandes, ventes, paiements et carnet de dettes clients.',
    span: 'normal'
  },
  {
    icon: 'pi pi-wallet',
    title: 'Finances',
    description: 'Suivi des flux financiers et consolidation de vos indicateurs clés.',
    span: 'wide'
  },
  {
    icon: 'pi pi-truck',
    title: 'Fournisseurs',
    description: 'Gestion des partenaires, dettes et journaux fournisseurs.',
    span: 'normal'
  },
  {
    icon: 'pi pi-chart-bar',
    title: 'Analytics',
    description: 'Tableaux de bord et métriques pour décider avec des données concrètes.',
    span: 'normal'
  },
  {
    icon: 'pi pi-shield',
    title: 'Accès & audit',
    description: 'Utilisateurs, rôles et journal d’audit pour une gouvernance sereine.',
    span: 'wide'
  }
]

export const defaultApplicationSlug = 'stockify'

export const betaPlanNotice = 'Plan Starter — 1 mois d’essai gratuit'
