<template>
  <AuthPageLayout
    v-if="!result"
    title="Créer un compte"
    subtitle="Configurez votre espace de gestion pour votre commerce."
    wide
  >
    <RegisterForm @success="result = $event" />
  </AuthPageLayout>

  <AuthPageLayout v-else wide>
    <RegisterSuccess :result="result" />
  </AuthPageLayout>
</template>

<script setup>
import { ref } from 'vue'
import AuthPageLayout from '@/domains/auth/components/AuthPageLayout.vue'
import RegisterForm from '@/domains/marketing/components/RegisterForm.vue'
import RegisterSuccess from '@/domains/marketing/components/RegisterSuccess.vue'

const result = ref(null)
</script>
