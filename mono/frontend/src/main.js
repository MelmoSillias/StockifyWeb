import { createApp } from 'vue'
import { createPinia } from 'pinia'

import ConfirmationService from 'primevue/confirmationservice'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import Tooltip from 'primevue/tooltip'
import Aura from '@primeuix/themes/aura'

import App from '@/App.vue'
import { applyApiClientConfig } from '@/lib/axios'
import { setAppRouter } from '@/lib/routerContext'
import router from '@/router'
import { useAuthStore } from '@/domains/auth/stores/auth'

import '@/assets/main.css'
import 'primeicons/primeicons.css'

applyApiClientConfig()

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(PrimeVue, {
  ripple: true,
  theme: {
    preset: Aura,
    options: {
      prefix: 'p',
      darkModeSelector: '.app-dark',
      cssLayer: false
    }
  }
})
app.use(ConfirmationService)
app.use(ToastService)
app.directive('tooltip', Tooltip)

setAppRouter(router)

const authStore = useAuthStore(pinia)
await authStore.restoreSession()

app.use(router)
await router.isReady()
app.mount('#app')
