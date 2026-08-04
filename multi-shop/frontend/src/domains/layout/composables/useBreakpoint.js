import { onMounted, onUnmounted, ref } from 'vue'

const queries = {
  narrow: '(max-width: 479px)',
  mobile: '(max-width: 767px)',
  tablet: '(max-width: 1023px)',
  compact: '(max-width: 1279px)'
}

const createMediaQueryList = (query) => {
  if (typeof window === 'undefined') {
    return null
  }

  return window.matchMedia(query)
}

const readMatches = () => {
  const narrow = createMediaQueryList(queries.narrow)
  const mobile = createMediaQueryList(queries.mobile)
  const tablet = createMediaQueryList(queries.tablet)
  const compact = createMediaQueryList(queries.compact)

  return {
    isNarrow: narrow?.matches ?? false,
    isMobile: mobile?.matches ?? false,
    isTablet: tablet?.matches ?? false,
    isCompact: compact?.matches ?? false
  }
}

export function useBreakpoint() {
  const isNarrow = ref(false)
  const isMobile = ref(false)
  const isTablet = ref(false)
  const isCompact = ref(false)

  const sync = () => {
    const matches = readMatches()
    isNarrow.value = matches.isNarrow
    isMobile.value = matches.isMobile
    isTablet.value = matches.isTablet
    isCompact.value = matches.isCompact
  }

  const listeners = []

  onMounted(() => {
    sync()

    Object.values(queries).forEach((query) => {
      const mediaQueryList = createMediaQueryList(query)

      if (!mediaQueryList) {
        return
      }

      const handler = () => sync()
      mediaQueryList.addEventListener('change', handler)
      listeners.push({ mediaQueryList, handler })
    })
  })

  onUnmounted(() => {
    listeners.forEach(({ mediaQueryList, handler }) => {
      mediaQueryList.removeEventListener('change', handler)
    })
  })

  return {
    isNarrow,
    isMobile,
    isTablet,
    isCompact
  }
}
