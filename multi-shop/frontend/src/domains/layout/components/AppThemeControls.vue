<script setup>
import { computed } from 'vue'
import { storeToRefs } from 'pinia'

import ColorPicker from 'primevue/colorpicker'
import SelectButton from 'primevue/selectbutton'
import Tag from 'primevue/tag'

import { useLayoutTheme } from '@/domains/layout/composables/useLayoutTheme'
import { useLayoutStore } from '@/domains/layout/stores/layout'

const presetAccentMap = {
  emerald: '#10b981',
  cyan: '#06b6d4',
  amber: '#f59e0b',
  rose: '#f43f5e',
  orange: '#F79E1B'
}

defineProps({
  compact: {
    type: Boolean,
    default: false
  }
})

const layoutStore = useLayoutStore()
const {
  themeName,
  accentName,
  accentColor,
  surfaceName,
  fontName,
  density,
  textScale,
  radius,
  darkMode,
  sidebarMode,
  motionPreset,
  gradientMode,
  layoutStyle,
  sidebarLogoPosition,
  sidebarProfilePosition,
  sidebarSearchPosition,
  topbarLogoVisibility,
  topbarProfilePosition,
  topbarSearchPosition
} = storeToRefs(layoutStore)

const {
  options,
  setThemeName,
  setAccentName,
  setAccentColor,
  setSurfaceName,
  setFontName,
  setDensity,
  setTextScale,
  setRadius,
  setDarkMode,
  setSidebarMode,
  setMotionPreset,
  setGradientMode,
  setLayoutStyle,
  setSidebarLogoPosition,
  setSidebarProfilePosition,
  setSidebarSearchPosition,
  setTopbarLogoVisibility,
  setTopbarProfilePosition,
  setTopbarSearchPosition
} = useLayoutTheme()

const accentPreviewHex = computed(() => {
  const resolved = accentName.value === 'custom'
    ? accentColor.value || '#10b981'
    : presetAccentMap[accentName.value] || '#10b981'

  return resolved.replace('#', '')
})

const updateAccentColor = (value) => {
  if (!value) {
    return
  }

  setAccentColor(value.startsWith('#') ? value : `#${value}`)
}
</script>

<template>
  <div class="app-theme-controls" :class="{ 'app-theme-controls--compact': compact }">
    <section class="app-theme-card app-theme-card--hero">
      <div class="app-theme-card__hero-copy">
        <p class="app-theme-card__eyebrow">Preferences UX</p>
        <h3>Un seul panneau pour toute l'app</h3>
        <p>Les reglages ci-dessous influent sur le shell, les pages de contenu, la page de login et les composants PrimeVue les plus utilises.</p>
      </div>
      <div class="app-theme-card__meta">
        <Tag :value="themeName" rounded />
        <Tag :value="fontName" severity="secondary" rounded />
        <Tag :value="textScale" severity="contrast" rounded />
      </div>
    </section>

    <div class="app-theme-grid">
      <section class="app-theme-card">
        <div class="app-theme-card__header">
          <div>
            <p class="app-theme-card__eyebrow">Ambiance</p>
            <h3>Theme et surfaces</h3>
          </div>
        </div>

        <div class="app-theme-field">
          <span>Theme</span>
          <SelectButton :model-value="themeName" :options="options.themes" option-label="label" option-value="value" @update:modelValue="setThemeName" />
        </div>

        <div class="app-theme-field">
          <span>Surface</span>
          <SelectButton :model-value="surfaceName" :options="options.surfaces" option-label="label" option-value="value" @update:modelValue="setSurfaceName" />
        </div>

        <div class="app-theme-field">
          <span>Mode sombre</span>
          <SelectButton :model-value="darkMode" :options="options.darkModes" option-label="label" option-value="value" @update:modelValue="setDarkMode" />
        </div>

        <div class="app-theme-field">
          <span>Fond</span>
          <SelectButton :model-value="gradientMode" :options="options.gradientModes" option-label="label" option-value="value" @update:modelValue="setGradientMode" />
        </div>
      </section>

      <section class="app-theme-card">
        <div class="app-theme-card__header">
          <div>
            <p class="app-theme-card__eyebrow">Typographie</p>
            <h3>Police et lisibilite</h3>
          </div>
        </div>

        <div class="app-theme-field">
          <span>Police</span>
          <SelectButton :model-value="fontName" :options="options.fonts" option-label="label" option-value="value" @update:modelValue="setFontName" />
        </div>

        <div class="app-theme-field">
          <span>Taille du texte</span>
          <SelectButton :model-value="textScale" :options="options.textScales" option-label="label" option-value="value" @update:modelValue="setTextScale" />
        </div>

        <div class="app-theme-field">
          <span>Densite</span>
          <SelectButton :model-value="density" :options="options.densities" option-label="label" option-value="value" @update:modelValue="setDensity" />
        </div>

        <div class="app-theme-field">
          <span>Rayon</span>
          <SelectButton :model-value="radius" :options="options.radii" option-label="label" option-value="value" @update:modelValue="setRadius" />
        </div>
      </section>

      <section class="app-theme-card">
        <div class="app-theme-card__header">
          <div>
            <p class="app-theme-card__eyebrow">Accent</p>
            <h3>Preset ou couleur libre</h3>
          </div>
          <div class="app-theme-accent-preview" :style="{ '--app-theme-preview': `#${accentPreviewHex}` }"></div>
        </div>

        <div class="app-theme-field">
          <span>Preset</span>
          <SelectButton :model-value="accentName" :options="options.accents" option-label="label" option-value="value" @update:modelValue="setAccentName" />
        </div>

        <div class="app-theme-field app-theme-field--accent-picker">
          <span>Color picker</span>
          <div class="app-theme-accent-picker">
            <ColorPicker format="hex" :model-value="accentPreviewHex" @update:modelValue="updateAccentColor" />
            <div>
              <strong>#{{ accentPreviewHex.toUpperCase() }}</strong>
              <p>Les variantes forte et douce sont calculees automatiquement.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="app-theme-card">
        <div class="app-theme-card__header">
          <div>
            <p class="app-theme-card__eyebrow">Mouvement</p>
            <h3>Navigation et rythme</h3>
          </div>
        </div>

        <div class="app-theme-field">
          <span>Sidebar</span>
          <SelectButton :model-value="sidebarMode" :options="options.sidebarModes" option-label="label" option-value="value" @update:modelValue="setSidebarMode" />
        </div>

        <div class="app-theme-field">
          <span>Mode layout</span>
          <SelectButton :model-value="layoutStyle" :options="options.layoutStyles" option-label="label" option-value="value" @update:modelValue="setLayoutStyle" />
        </div>

        <div class="app-theme-field">
          <span>Animations</span>
          <SelectButton :model-value="motionPreset" :options="options.motionPresets" option-label="label" option-value="value" @update:modelValue="setMotionPreset" />
        </div>
      </section>

      <section class="app-theme-card">
        <div class="app-theme-card__header">
          <div>
            <p class="app-theme-card__eyebrow">Sidebar</p>
            <h3>Placement et composition</h3>
          </div>
        </div>

        <div class="app-theme-field">
          <span>Logo</span>
          <SelectButton :model-value="sidebarLogoPosition" :options="options.sidebarLogoPositions" option-label="label" option-value="value" @update:modelValue="setSidebarLogoPosition" />
        </div>

        <div class="app-theme-field">
          <span>Profil</span>
          <SelectButton :model-value="sidebarProfilePosition" :options="options.sidebarProfilePositions" option-label="label" option-value="value" @update:modelValue="setSidebarProfilePosition" />
        </div>

        <div class="app-theme-field">
          <span>Recherche</span>
          <SelectButton :model-value="sidebarSearchPosition" :options="options.sidebarSearchPositions" option-label="label" option-value="value" @update:modelValue="setSidebarSearchPosition" />
        </div>
      </section>

      <section class="app-theme-card">
        <div class="app-theme-card__header">
          <div>
            <p class="app-theme-card__eyebrow">Topbar</p>
            <h3>Alignements utiles</h3>
          </div>
        </div>

        <div class="app-theme-field">
          <span>Logo</span>
          <SelectButton :model-value="topbarLogoVisibility" :options="options.topbarLogoVisibilities" option-label="label" option-value="value" @update:modelValue="setTopbarLogoVisibility" />
        </div>

        <div class="app-theme-field">
          <span>Profil</span>
          <SelectButton :model-value="topbarProfilePosition" :options="options.topbarProfilePositions" option-label="label" option-value="value" @update:modelValue="setTopbarProfilePosition" />
        </div>

        <div class="app-theme-field">
          <span>Recherche</span>
          <SelectButton :model-value="topbarSearchPosition" :options="options.topbarSearchPositions" option-label="label" option-value="value" @update:modelValue="setTopbarSearchPosition" />
        </div>
      </section>
    </div>
  </div>
</template>