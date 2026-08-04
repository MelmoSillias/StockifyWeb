import { onMounted, onUnmounted, ref } from 'vue'

export function useScrollReveal(options = {}) {
  const { threshold = 0.12, rootMargin = '0px 0px -8% 0px' } = options
  const elements = ref([])

  let observer = null

  const register = (element) => {
    if (!element || elements.value.includes(element)) {
      return
    }

    elements.value.push(element)
    observer?.observe(element)
  }

  onMounted(() => {
    observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-revealed')
          observer?.unobserve(entry.target)
        }
      })
    }, { threshold, rootMargin })

    elements.value.forEach((element) => observer.observe(element))
  })

  onUnmounted(() => {
    observer?.disconnect()
    observer = null
  })

  return { register }
}
