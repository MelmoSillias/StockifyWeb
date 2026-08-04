import { reactive } from 'vue'

export const useCrudDialog = (createEmptyForm) => {
  const dialog = reactive({
    visible: false,
    mode: 'create',
    formData: createEmptyForm(),
    get dialogTitle() {
      return this.mode === 'create' ? 'Ajouter' : 'Modifier'
    }
  })

  const openCreate = () => {
    dialog.mode = 'create'
    dialog.formData = createEmptyForm()
    dialog.visible = true
  }

  const openEdit = (payload) => {
    dialog.mode = 'edit'
    dialog.formData = {
      ...createEmptyForm(),
      ...payload
    }
    dialog.visible = true
  }

  const close = () => {
    dialog.visible = false
  }

  return Object.assign(dialog, {
    openCreate,
    openEdit,
    close
  })
}