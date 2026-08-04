<template>
  <Card class="dashboard-panel dashboard-carousel">
    <template #title>
      <div class="dashboard-section-header">
        <div class="dashboard-section-header__leading">
          <span class="dashboard-section-header__icon">
            <i class="pi pi-bolt"></i>
          </span>
          <div>
            <h2 class="dashboard-section-header__title">Activité récente</h2>
            <p class="dashboard-section-header__subtitle">Données clés sur la période sélectionnée</p>
          </div>
        </div>
      </div>
    </template>
    <template #content>
      <div v-if="slides.length > 0" class="dashboard-carousel__body">
        <div class="dashboard-carousel__tabs" role="tablist">
          <button
            v-for="(slide, index) in slides"
            :key="slide.type"
            type="button"
            class="dashboard-carousel__tab"
            :class="{ 'dashboard-carousel__tab--active': activeIndex === index }"
            @click="goToSlide(index)"
          >
            {{ slide.shortTitle }}
          </button>
        </div>

        <Carousel
          v-model:page="activeIndex"
          :value="slides"
          :num-visible="1"
          :num-scroll="1"
          circular
          :show-indicators="false"
          :show-navigators="slides.length > 1"
        >
          <template #item="{ data }">
            <DashboardCarouselSlide
              :title="data.title"
              :icon="data.icon"
              :items="data.items"
              :type="data.type"
              :route-name="data.routeName"
              :empty-text="data.emptyText"
              :format-money="formatMoney"
              :format-date-time="formatDateTime"
              :format-decimal="formatDecimal"
            />
          </template>
        </Carousel>
      </div>
      <p v-else class="dashboard-carousel__empty">Aucune donnée disponible pour le carousel.</p>
    </template>
  </Card>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

import Card from 'primevue/card'
import Carousel from 'primevue/carousel'

import DashboardCarouselSlide from '@/domains/home/components/DashboardCarouselSlide.vue'
import { useDisplayFormatters } from '@/domains/shared/composables/useDisplayFormatters'

const props = defineProps({
  feed: {
    type: Object,
    default: null
  },
  visibleSlides: {
    type: Array,
    default: () => []
  }
})

const { formatMoney, formatDateTime, formatDecimal } = useDisplayFormatters()
const activeIndex = ref(0)

const slides = computed(() => {
  const feed = props.feed ?? {}

  return props.visibleSlides.map((definition) => ({
    type: definition.type,
    title: definition.title,
    shortTitle: definition.shortTitle,
    icon: definition.icon,
    routeName: definition.routeName,
    items: feed[definition.feedKey] ?? [],
    emptyText: definition.emptyText
  }))
})

watch(slides, (nextSlides) => {
  if (activeIndex.value >= nextSlides.length) {
    activeIndex.value = 0
  }
})

const goToSlide = (index) => {
  activeIndex.value = index
}
</script>

<style scoped>
.dashboard-carousel__empty {
  margin: 0;
  color: var(--layout-text-muted);
}
</style>
