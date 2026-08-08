<template>
  <div class="app-preview">
    <div class="app-preview__chrome">
      <div class="app-preview__toolbar">
        <span class="app-preview__dot"></span>
        <span class="app-preview__dot"></span>
        <span class="app-preview__dot"></span>
        <span class="app-preview__title">{{ title }}</span>
      </div>
      <div class="app-preview__screen">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'Lafia Sugu'
  }
})
</script>

<style scoped>
.app-preview {
  width: 100%;
  max-width: 36rem;
}

.app-preview__chrome {
  border: 1px solid var(--mkt-border);
  border-radius: var(--mkt-radius);
  background: var(--mkt-surface);
  box-shadow: var(--mkt-shadow-md);
  overflow: hidden;
}

.app-preview__toolbar {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid var(--mkt-border);
  background: var(--mkt-surface-muted);
}

.app-preview__dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background: #cbd5e1;
}

.app-preview__title {
  margin-left: 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--mkt-text-muted);
}

.app-preview__screen {
  background: var(--mkt-surface-muted);
}

@media (max-width: 400px) {
  .app-preview {
    max-width: 100%;
  }
}
</style>
