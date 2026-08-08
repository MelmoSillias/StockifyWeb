export const featureLabels = {
  'stockify.multi_shop': 'Multi-boutiques',
  'stockify.orders': 'Commandes',
  'stockify.quotes': 'Devis',
  'stockify.analytics': 'Rapports et statistiques',
  'stockify.suppliers': 'Fournisseurs'
}

export const showcaseSection = {
  id: 'stock',
  eyebrow: 'Suivi du stock',
  title: 'Anticipez les ruptures et les réapprovisionnements.',
  description: 'Consultez vos niveaux de stock, recevez des alertes de seuil et tracez chaque entrée ou sortie depuis un seul endroit.',
  cta: 'Créer mon compte',
  mockup: {
    theme: 'light',
    title: 'Stock en direct',
    stats: [
      { label: 'Produits actifs', value: '1 284' },
      { label: 'Alertes', value: '7', hint: 'Seuil bas' }
    ],
    rows: ['Huile 5L — 12 restants', 'Riz 25kg — réappro demain', 'Savon — stock OK']
  }
}

export const featureModules = [
  {
    icon: 'pi pi-box',
    title: 'Stock et inventaire',
    description: 'Niveaux, alertes, mouvements et traçabilité de vos produits.'
  },
  {
    icon: 'pi pi-shopping-cart',
    title: 'Ventes et commandes',
    description: 'Encaissement, suivi des ventes, commandes clients et créances.'
  },
  {
    icon: 'pi pi-inbox',
    title: 'Produits et catégories',
    description: 'Catalogue structuré avec catégories, variantes et unités.'
  },
  {
    icon: 'pi pi-users',
    title: 'Clients',
    description: 'Fiches clients, historique et suivi des dettes.'
  },
  {
    icon: 'pi pi-truck',
    title: 'Fournisseurs',
    description: 'Partenaires, commandes et journaux fournisseurs.'
  },
  {
    icon: 'pi pi-chart-bar',
    title: 'Rapports',
    description: 'Indicateurs d\'activité pour suivre votre commerce.'
  },
  {
    icon: 'pi pi-shop',
    title: 'Multi-boutiques',
    description: 'Plusieurs points de vente depuis une même interface.'
  },
  {
    icon: 'pi pi-shield',
    title: 'Utilisateurs et accès',
    description: 'Comptes, rôles et journal d\'activité.'
  }
]

export const defaultApplicationSlug = 'stockify'

export const betaPlanNotice = 'Plan Starter — 1 mois d\'essai gratuit'
