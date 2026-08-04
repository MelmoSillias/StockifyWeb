<template>
  <div class="stats-grid">
    <Card v-for="item in items" :key="item.label" class="stats-card">
      <template #content>
        <div class="stats-card__content">
          <div class="stats-card__icon">
            <i :class="item.icon"></i>
          </div>
          <div>
            <div class="stats-card__header">
              <p class="stats-card__label">{{ item.label }}</p>
              <Tag value="Live" severity="secondary" rounded />
            </div>
            <p class="stats-card__value">{{ item.value }}</p>
            <p class="stats-card__hint">{{ item.hint }}</p>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import Card from 'primevue/card'
import Tag from 'primevue/tag'

defineProps({
  items: {
    type: Array,
    default: () => []
  }
})
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
}

.stats-card__content {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.stats-card__header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.stats-card__icon {
  display: grid;
  place-items: center;
  width: 3rem;
  height: 3rem;
  border-radius: 0.9rem;
  background: color-mix(in srgb, var(--p-primary-color) 12%, white);
  color: var(--p-primary-color);
  font-size: 1.15rem;
}

.stats-card__label,
.stats-card__value,
.stats-card__hint {
  margin: 0;
}

.stats-card__label {
  color: var(--p-text-muted-color);
  font-size: 0.9rem;
}

.stats-card__value {
  margin-top: 0.35rem;
  font-size: 1.6rem;
  font-weight: 700;
}

.stats-card__hint {
  margin-top: 0.35rem;
  color: var(--p-text-muted-color);
  font-size: 0.85rem;
}
</style>