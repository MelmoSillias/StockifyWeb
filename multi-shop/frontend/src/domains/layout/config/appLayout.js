import { appConfig } from '@/config/app'
import { layoutDefaults, layoutOptionSets, themeDefaultAccents } from '@/config/layout'

export { themeDefaultAccents, layoutOptionSets }

export const appShellBrand = appConfig.branding

export const appNavigation = appConfig.navigation.items

export const appShellDefaults = {
  navigation: appConfig.navigation,
  appearance: layoutDefaults.appearance,
  layout: layoutDefaults.layout,
  features: layoutDefaults.features
}
