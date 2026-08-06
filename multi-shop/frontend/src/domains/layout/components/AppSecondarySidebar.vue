<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'

import { useLayoutStore } from '@/domains/layout/stores/layout'

const props = defineProps({
  group: {
    type: Object,
    default: null
  },
  visible: {
    type: Boolean,
    default: false
  },
  activeKey: {
    type: String,
    default: null
  }
})

const router = useRouter()
const layoutStore = useLayoutStore()
const { layoutStyle } = storeToRefs(layoutStore)

const isFloating = computed(() => layoutStyle.value === 'detached')
const transitionName = computed(() => (isFloating.value ? 'secondary-sidebar-float' : 'secondary-sidebar'))

const panelRef = ref(null)
const contentRef = ref(null)

let heightAnimationToken = 0

const items = computed(() => props.group?.items || [])

const waitForPaint = () =>
  new Promise((resolve) => {
    requestAnimationFrame(() => {
      requestAnimationFrame(resolve)
    })
  })

const measurePanelHeight = (panel = panelRef.value) => {
  if (!panel) {
    return 0
  }

  const previousHeight = panel.style.height
  const previousOverflow = panel.style.overflow

  panel.style.height = 'auto'
  panel.style.overflow = 'visible'

  const height = Math.ceil(panel.getBoundingClientRect().height)

  panel.style.height = previousHeight
  panel.style.overflow = previousOverflow

  return height
}

const finalizePanelHeight = (panel) => {
  if (!panel) {
    return
  }

  const height = Math.ceil(panel.getBoundingClientRect().height)
  panel.style.height = `${height}px`
  panel.style.overflow = ''
  panel.classList.remove('app-secondary-sidebar--animating')
}

const animateFloatingHeight = (panel, targetHeight) =>
  new Promise((resolve) => {
    const token = ++heightAnimationToken
    const startHeight = panel.getBoundingClientRect().height

    if (Math.abs(startHeight - targetHeight) < 2) {
      finalizePanelHeight(panel)
      resolve()
      return
    }

    panel.classList.add('app-secondary-sidebar--animating')
    panel.style.overflow = 'hidden'
    panel.style.height = `${startHeight}px`
    panel.offsetHeight

    requestAnimationFrame(() => {
      if (token !== heightAnimationToken) {
        resolve()
        return
      }

      panel.style.height = `${targetHeight}px`

      const onEnd = (event) => {
        if (event.propertyName !== 'height') {
          return
        }

        panel.removeEventListener('transitionend', onEnd)

        if (token !== heightAnimationToken) {
          resolve()
          return
        }

        if (props.visible && props.group) {
          finalizePanelHeight(panel)
        }

        resolve()
      }

      panel.addEventListener('transitionend', onEnd)
    })
  })

const runFloatingLayout = async (panel = panelRef.value) => {
  if (!panel || !isFloating.value || !props.visible || !props.group) {
    return
  }

  await waitForPaint()
  await animateFloatingHeight(panel, measurePanelHeight(panel))
}

const handleItemClick = (item) => {
  if (item.routeName) {
    router.push({ name: item.routeName })
  }
}

const isActive = (item) => {
  return item.key === props.activeKey
}

const onBeforeEnter = (el) => {
  if (!isFloating.value) {
    return
  }

  el.style.height = '0px'
  el.style.overflow = 'hidden'
}

const onEnter = async (el, done) => {
  if (!isFloating.value) {
    done()
    return
  }

  await waitForPaint()
  await animateFloatingHeight(el, measurePanelHeight(el))
  done()
}

const onLeave = async (el, done) => {
  if (!isFloating.value) {
    done()
    return
  }

  ++heightAnimationToken
  el.classList.add('app-secondary-sidebar--animating')
  el.style.height = `${el.getBoundingClientRect().height}px`
  el.style.overflow = 'hidden'
  el.offsetHeight

  await waitForPaint()

  requestAnimationFrame(() => {
    el.style.height = '0px'

    const finish = (event) => {
      if (event.propertyName !== 'height') {
        return
      }

      el.removeEventListener('transitionend', finish)
      done()
    }

    el.addEventListener('transitionend', finish)
  })
}

watch(
  () => props.group?.key,
  async () => {
    if (!isFloating.value || !props.visible) {
      return
    }

    await nextTick()
    runFloatingLayout()
  },
  { flush: 'post' }
)
</script>

<template>
  <Transition
    :name="transitionName"
    @before-enter="onBeforeEnter"
    @enter="onEnter"
    @leave="onLeave"
  >
    <aside
      v-if="visible && group"
      ref="panelRef"
      class="app-secondary-sidebar"
      :class="{ 'app-secondary-sidebar--floating': isFloating }"
    >
      <div ref="contentRef" class="app-secondary-sidebar__content">
        <div class="app-secondary-sidebar__header">
          <i v-if="group.icon" :class="['app-secondary-sidebar__icon', group.icon]"></i>
          <h3 class="app-secondary-sidebar__title">{{ group.label }}</h3>
        </div>
        <div class="app-secondary-sidebar__items">
          <button
            v-for="item in items"
            :key="item.key"
            type="button"
            class="app-secondary-sidebar__item"
            :class="{ 'app-secondary-sidebar__item--active': isActive(item) }"
            @click="handleItemClick(item)"
          >
            <i v-if="item.icon" :class="['app-secondary-sidebar__item-icon', item.icon]"></i>
            <span class="app-secondary-sidebar__item-label">{{ item.label }}</span>
          </button>
        </div>
      </div>
    </aside>
  </Transition>
</template>
