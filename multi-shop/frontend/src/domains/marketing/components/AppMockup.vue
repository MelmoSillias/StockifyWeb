<template>
  <div class="app-mockup" :class="[`app-mockup--${variant}`, { 'app-mockup--offset': offset }]">
    <div class="app-mockup__frame">
      <div class="app-mockup__notch"></div>
      <div class="app-mockup__screen">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'default'
  },
  offset: {
    type: Boolean,
    default: false
  }
})
</script>

<style scoped>
.app-mockup {
  position: relative;
  width: min(100%, 15rem);
}

.app-mockup--offset {
  transform: translateY(1.5rem);
}

.app-mockup__frame {
  border-radius: 2rem;
  padding: 0.65rem;
  background: linear-gradient(160deg, #2a2d45 0%, #12131f 100%);
  box-shadow: var(--mkt-shadow-phone, 0 32px 80px rgba(15, 19, 31, 0.32));
  border: 2px solid rgba(255, 255, 255, 0.08);
}

.app-mockup--light .app-mockup__frame {
  background: linear-gradient(160deg, #ffffff 0%, #eef1f8 100%);
  border-color: rgba(17, 24, 39, 0.08);
}

.app-mockup__notch {
  width: 36%;
  height: 0.35rem;
  margin: 0 auto 0.5rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
}

.app-mockup--light .app-mockup__notch {
  background: rgba(17, 24, 39, 0.08);
}

.app-mockup__screen {
  border-radius: 1.4rem;
  overflow: hidden;
  background: #0f111a;
  min-height: 18rem;
}

.app-mockup--light .app-mockup__screen {
  background: #f3f5fa;
}

@media (max-width: 900px) {
  .app-mockup--offset {
    transform: none;
  }

  .app-mockup__screen {
    min-height: 16rem;
  }
}

@media (max-width: 400px) {
  .app-mockup {
    width: min(100%, 12.5rem);
  }

  .app-mockup__screen {
    min-height: 14.5rem;
  }
}
</style>
