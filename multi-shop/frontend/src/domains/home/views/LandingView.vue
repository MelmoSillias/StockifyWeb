<template>
  <section class="landing-page">
    <div class="landing-page__hero">
      <p class="landing-page__eyebrow">Stockify</p>
      <h1 class="landing-page__title">Gerez votre catalogue et votre stock sur une seule plateforme.</h1>
      <p class="landing-page__description">
        Connectez-vous pour piloter vos boutiques, suivre vos mouvements de stock
        et garder une visibilite claire sur vos seuils d'alerte.
      </p>
      <Button label="Se connecter" icon="pi pi-sign-in" size="large" @click="router.push({ name: 'login' })" />
    </div>
  </section>
</template>

<script setup>
import { useRouter } from 'vue-router'
import Button from 'primevue/button'

const router = useRouter()
</script>

<style scoped>
.landing-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 2rem;
  background: var(--layout-shell-bg);
}

.landing-page__hero {
  width: min(100%, 52rem);
  text-align: center;
  background: color-mix(in srgb, var(--layout-panel-bg) 92%, transparent);
  border: 1px solid var(--layout-panel-border);
  border-radius: var(--layout-radius-lg);
  padding: clamp(1.5rem, 4vw, 3rem);
  box-shadow: var(--layout-shadow-soft);
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: center;
}

.landing-page__eyebrow {
  margin: 0;
  font-size: var(--layout-font-size-sm);
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--layout-text-muted);
}

.landing-page__title {
  margin: 0;
  font-size: clamp(2rem, 1.6rem + 1.4vw, 3rem);
}

.landing-page__description {
  margin: 0;
  color: var(--layout-text-muted);
  line-height: 1.6;
}
</style>
