<template>
  <div class="mockup-dashboard" :class="`mockup-dashboard--${theme}`">
    <div class="mockup-dashboard__header">
      <span></span><span></span><span></span>
      <p>{{ title }}</p>
    </div>
    <div class="mockup-dashboard__stats">
      <div v-for="stat in stats" :key="stat.label" class="mockup-dashboard__stat">
        <small>{{ stat.label }}</small>
        <strong>{{ stat.value }}</strong>
        <em v-if="stat.hint">{{ stat.hint }}</em>
      </div>
    </div>
    <div v-if="bars.length" class="mockup-dashboard__chart">
      <div v-for="(bar, index) in bars" :key="index" :style="{ height: `${bar}%` }"></div>
    </div>
    <ul v-if="rows.length" class="mockup-dashboard__rows">
      <li v-for="row in rows" :key="row">
        <span></span>
        {{ row }}
      </li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  theme: {
    type: String,
    default: 'dark'
  },
  title: {
    type: String,
    default: 'LafiaSugu'
  },
  stats: {
    type: Array,
    default: () => []
  },
  bars: {
    type: Array,
    default: () => []
  },
  rows: {
    type: Array,
    default: () => []
  }
})
</script>

<style scoped>
.mockup-dashboard {
  padding: 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  min-height: 18rem;
}

.mockup-dashboard--light {
  background: #f3f5fa;
}

.mockup-dashboard__header {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.68rem;
  opacity: 0.7;
}

.mockup-dashboard__header span {
  width: 0.45rem;
  height: 0.45rem;
  border-radius: 999px;
  background: currentColor;
  opacity: 0.35;
}

.mockup-dashboard__header p {
  margin-left: auto;
}

.mockup-dashboard__stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.45rem;
}

.mockup-dashboard__stat {
  padding: 0.55rem;
  border-radius: 0.75rem;
  background: rgba(255, 255, 255, 0.06);
}

.mockup-dashboard--light .mockup-dashboard__stat {
  background: white;
  box-shadow: 0 4px 16px rgba(17, 24, 39, 0.06);
}

.mockup-dashboard__stat small,
.mockup-dashboard__stat em {
  display: block;
  font-size: 0.62rem;
  font-style: normal;
  opacity: 0.65;
}

.mockup-dashboard__stat strong {
  display: block;
  font-size: 1rem;
  margin: 0.15rem 0;
}

.mockup-dashboard__chart {
  display: flex;
  align-items: flex-end;
  gap: 0.3rem;
  height: 5rem;
  padding: 0.45rem;
  border-radius: 0.75rem;
  background: var(--mkt-accent-soft);
}

.mockup-dashboard__chart div {
  flex: 1;
  border-radius: 0.35rem 0.35rem 0.1rem 0.1rem;
  background: linear-gradient(180deg, var(--mkt-accent), color-mix(in srgb, var(--mkt-accent) 40%, transparent));
}

.mockup-dashboard__rows {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.mockup-dashboard__rows li {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.72rem;
  padding: 0.45rem 0.55rem;
  border-radius: 0.55rem;
  background: rgba(255, 255, 255, 0.05);
}

.mockup-dashboard--light .mockup-dashboard__rows li {
  background: white;
}

.mockup-dashboard__rows span {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 999px;
  background: var(--mkt-accent);
  flex-shrink: 0;
}
</style>
