<template>
  <article class="pricing-card quote-card">
    <div class="pricing-card__head">
      <p class="pricing-card__name">Sur devis</p>
      <p class="pricing-card__price">
        <strong>Personnalisé</strong>
      </p>
      <p class="quote-card__hint">Volumes et fonctionnalités adaptés à votre réseau.</p>
    </div>

    <ul class="pricing-card__features">
      <li><i class="pi pi-check"></i> Quotas sur mesure</li>
      <li><i class="pi pi-check"></i> Fonctionnalités à la carte</li>
      <li><i class="pi pi-check"></i> Accompagnement dédié</li>
    </ul>

    <button type="button" class="pricing-card__cta pricing-card__cta--primary" @click="visible = true">
      Demander un devis
    </button>

    <Dialog
      v-model:visible="visible"
      modal
      header="Demande sur devis"
      :style="{ width: '32rem' }"
      :breakpoints="{ '640px': '94vw' }"
    >
      <form class="quote-form" @submit.prevent="submit">
        <div class="quote-form__field">
          <label for="qr-name">Nom *</label>
          <InputText id="qr-name" v-model="form.contactName" fluid required />
        </div>
        <div class="quote-form__field">
          <label for="qr-email">E-mail *</label>
          <InputText id="qr-email" v-model="form.email" type="email" fluid required />
        </div>
        <div class="quote-form__field">
          <label for="qr-phone">Téléphone</label>
          <InputText id="qr-phone" v-model="form.phone" fluid />
        </div>
        <div class="quote-form__row">
          <div class="quote-form__field">
            <label for="qr-shops">Boutiques souhaitées</label>
            <InputNumber id="qr-shops" v-model="form.maxShops" :min="1" fluid />
          </div>
          <div class="quote-form__field">
            <label for="qr-users">Utilisateurs souhaités</label>
            <InputNumber id="qr-users" v-model="form.maxUsers" :min="1" fluid />
          </div>
        </div>
        <div class="quote-form__field">
          <label>Fonctionnalités</label>
          <div class="quote-form__checks">
            <label v-for="feature in featureOptions" :key="feature.code" class="quote-form__check">
              <Checkbox v-model="form.features" :input-id="feature.code" :value="feature.code" />
              <span>{{ feature.label }}</span>
            </label>
          </div>
        </div>
        <div class="quote-form__field">
          <label for="qr-message">Message</label>
          <Textarea id="qr-message" v-model="form.message" rows="3" fluid auto-resize />
        </div>
        <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
        <Message v-if="success" severity="success" :closable="false">{{ success }}</Message>
        <div class="quote-form__actions">
          <Button type="button" label="Annuler" severity="secondary" text :disabled="saving" @click="visible = false" />
          <Button type="submit" label="Envoyer" icon="pi pi-send" :loading="saving" />
        </div>
      </form>
    </Dialog>
  </article>
</template>

<script setup>
import { reactive, ref } from 'vue'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import { quoteRequestsService } from '@/domains/marketing/services/quoteRequestsService'

const visible = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

const featureOptions = [
  { code: 'stockify.orders', label: 'Commandes' },
  { code: 'stockify.quotes', label: 'Devis' },
  { code: 'stockify.analytics', label: 'Analytics' },
  { code: 'stockify.suppliers', label: 'Fournisseurs' },
  { code: 'stockify.multi_shop', label: 'Multi-boutiques' }
]

const form = reactive({
  contactName: '',
  email: '',
  phone: '',
  maxShops: 10,
  maxUsers: 50,
  features: ['stockify.orders', 'stockify.quotes', 'stockify.analytics', 'stockify.suppliers'],
  message: ''
})

const submit = async () => {
  saving.value = true
  error.value = ''
  success.value = ''

  try {
    await quoteRequestsService.submit({
      contactName: form.contactName,
      email: form.email,
      phone: form.phone || null,
      requestedQuotas: {
        max_shops: form.maxShops || 0,
        max_users: form.maxUsers || 0
      },
      requestedFeatures: [...form.features],
      message: form.message || null
    })
    success.value = 'Votre demande a été envoyée. Notre équipe vous recontactera rapidement.'
  } catch (err) {
    error.value = err?.response?.data?.error
      || 'Impossible d’envoyer la demande pour le moment.'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.pricing-card {
  position: relative;
  padding: 2rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  min-height: 100%;
  border-radius: var(--mkt-radius);
  border: 1px solid var(--mkt-border-light);
  background: white;
  box-shadow: var(--mkt-shadow-sm);
  transition: transform 220ms ease, box-shadow 220ms ease;
}

.pricing-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--mkt-shadow);
}

.pricing-card__name {
  color: var(--mkt-light-muted);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-size: 0.78rem;
  font-weight: 700;
}

.pricing-card__price strong {
  font-size: 2.4rem;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.pricing-card__features {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  flex: 1;
}

.pricing-card__features li {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  color: var(--mkt-light-muted);
  font-size: 0.92rem;
}

.pricing-card__features i {
  color: var(--mkt-accent);
  font-size: 0.85rem;
}

.pricing-card__cta {
  display: inline-flex;
  justify-content: center;
  padding: 0.85rem 1rem;
  border-radius: var(--mkt-radius-pill);
  border: 1px solid var(--mkt-border-light);
  color: var(--mkt-light-text);
  font-weight: 700;
  background: white;
  cursor: pointer;
}

.pricing-card__cta--primary {
  background: var(--mkt-accent);
  border-color: transparent;
  color: white;
  box-shadow: 0 10px 32px var(--mkt-accent-glow);
}

.quote-card__hint {
  margin: 0.35rem 0 0;
  color: var(--mkt-light-muted);
  font-size: 0.9rem;
}

.quote-form {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.quote-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.quote-form__field label {
  font-size: 0.85rem;
  font-weight: 600;
}

.quote-form__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.quote-form__checks {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.quote-form__check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.quote-form__actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

@media (max-width: 400px) {
  .quote-form__row {
    grid-template-columns: 1fr;
  }
}
</style>
