import { ref, watch } from 'vue'

const slugify = (value) => value
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()
  .trim()
  .replace(/[^a-z0-9]+/g, '-')
  .replace(/^-+|-+$/g, '')
  .slice(0, 48)

export function useAccountSlug(accountNameRef) {
  const accountSlug = ref('')
  const slugTouched = ref(false)

  watch(accountNameRef, (name) => {
    if (slugTouched.value) {
      return
    }

    accountSlug.value = slugify(name || '')
  })

  const onSlugInput = () => {
    slugTouched.value = true
    accountSlug.value = slugify(accountSlug.value)
  }

  return {
    accountSlug,
    slugTouched,
    onSlugInput
  }
}
