export const themeDefaultAccents = {
  graphite: 'emerald',
  ocean: 'cyan',
  ember: 'amber',
  aurora: 'rose',
  canopy: 'emerald',
  dune: 'amber',
  cosmos: 'cyan'
}

export const layoutOptionSets = {
  themes: [
    { label: 'Graphite', value: 'graphite' },
    { label: 'Ocean', value: 'ocean' },
    { label: 'Ember', value: 'ember' },
    { label: 'Aurora', value: 'aurora' },
    { label: 'Canopy', value: 'canopy' },
    { label: 'Dune', value: 'dune' },
    { label: 'Cosmos', value: 'cosmos' }
  ],
  accents: [
    { label: 'Emerald', value: 'emerald' },
    { label: 'Cyan', value: 'cyan' },
    { label: 'Amber', value: 'amber' },
    { label: 'Rose', value: 'rose' },
    { label: 'Custom', value: 'custom' }
  ],
  surfaces: [
    { label: 'Slate', value: 'slate' },
    { label: 'Stone', value: 'stone' },
    { label: 'Zinc', value: 'zinc' }
  ],
  fonts: [
    { label: 'Space Grotesk', value: 'space' },
    { label: 'Sora', value: 'sora' },
    { label: 'IBM Plex Sans', value: 'plex' },
    { label: 'Manrope', value: 'manrope' },
    { label: 'Outfit', value: 'outfit' },
    { label: 'Plus Jakarta Sans', value: 'jakarta' },
    { label: 'Urbanist', value: 'urbanist' }
  ],
  densities: [
    { label: 'Compact', value: 'compact' },
    { label: 'Comfort', value: 'comfortable' },
    { label: 'Spacious', value: 'spacious' }
  ],
  textScales: [
    { label: 'Petit', value: 'small' },
    { label: 'Normal', value: 'normal' },
    { label: 'Grand', value: 'large' }
  ],
  radii: [
    { label: 'Sharp', value: 'sharp' },
    { label: 'Soft', value: 'soft' },
    { label: 'Round', value: 'round' }
  ],
  darkModes: [
    { label: 'Systeme', value: 'system' },
    { label: 'Clair', value: 'light' },
    { label: 'Sombre', value: 'dark' }
  ],
  sidebarModes: [
    { label: 'Fixed', value: 'fixed' },
    { label: 'Overlay', value: 'overlay' }
  ],
  motionPresets: [
    { label: 'Calm', value: 'calm' },
    { label: 'Expressive', value: 'expressive' },
    { label: 'Reduced', value: 'reduced' }
  ],
  sidebarLogoPositions: [
    { label: 'Haut', value: 'top' },
    { label: 'Milieu', value: 'center' }
  ],
  sidebarProfilePositions: [
    { label: 'Bas', value: 'bottom' },
    { label: 'Haut', value: 'top' },
    { label: 'Cache', value: 'hidden' }
  ],
  sidebarSearchPositions: [
    { label: 'Avant menu', value: 'before-menu' },
    { label: 'Apres menu', value: 'after-menu' },
    { label: 'Cache', value: 'hidden' }
  ],
  topbarProfilePositions: [
    { label: 'Fin', value: 'end' },
    { label: 'Debut', value: 'start' },
    { label: 'Cache', value: 'hidden' }
  ],
  topbarLogoVisibilities: [
    { label: 'Visible', value: 'visible' },
    { label: 'Cache', value: 'hidden' }
  ],
  topbarSearchPositions: [
    { label: 'Centre', value: 'center' },
    { label: 'Debut', value: 'start' },
    { label: 'Cache', value: 'hidden' }
  ],
  gradientModes: [
    { label: 'Degrade', value: 'gradient' },
    { label: 'Uni', value: 'solid' }
  ]
}

export const layoutDefaults = {
  appearance: {
    themeName: 'graphite',
    accentName: 'emerald',
    accentColor: null,
    surfaceName: 'slate',
    fontName: 'space',
    density: 'comfortable',
    textScale: 'normal',
    radius: 'soft',
    darkMode: 'system',
    motionPreset: 'expressive',
    gradientMode: 'gradient'
  },
  layout: {
    sidebarLogoPosition: 'top',
    sidebarProfilePosition: 'bottom',
    sidebarSearchPosition: 'hidden',
    topbarLogoVisibility: 'visible',
    topbarProfilePosition: 'end',
    topbarSearchPosition: 'center'
  },
  features: {
    breadcrumbs: true,
    topbarSearch: true,
    sidebarSearch: false,
    sidebarProfile: true,
    topbarProfile: true,
    preferencesPanel: true
  }
}
