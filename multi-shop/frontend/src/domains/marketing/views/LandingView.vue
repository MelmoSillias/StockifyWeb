<template>
  <div class="mkt-page">
    <MarketingShell landing>
      <LandingHero />
      <LandingBenefits />
      <LandingShowcase
        v-for="section in showcaseSections"
        :key="section.id"
        :section="section"
      />
      <LandingFeatures />
      <LandingTrust />
      <LandingPricing />
      <LandingCta />
    </MarketingShell>
  </div>
</template>

<script setup>
import MarketingShell from '@/domains/marketing/components/MarketingShell.vue'
import LandingHero from '@/domains/marketing/components/LandingHero.vue'
import LandingBenefits from '@/domains/marketing/components/LandingBenefits.vue'
import LandingShowcase from '@/domains/marketing/components/LandingShowcase.vue'
import LandingFeatures from '@/domains/marketing/components/LandingFeatures.vue'
import LandingPricing from '@/domains/marketing/components/LandingPricing.vue'
import LandingTrust from '@/domains/marketing/components/LandingTrust.vue'
import LandingCta from '@/domains/marketing/components/LandingCta.vue'
import { showcaseSections } from '@/domains/marketing/config/marketingContent'
</script>

<style>
@import '@/domains/marketing/assets/marketing.css';
</style>
