import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

export const useEntityActions = () => {
  let confirm = null

  try {
    confirm = useConfirm()
  } catch {
    confirm = null
  }

  const toast = useToast()

  const showToast = (toastPayload) => {
    toast.add({
      life: 3000,
      ...toastPayload
    })
  }

  const showSuccess = (detail, summary = 'Operation reussie') => {
    showToast({ severity: 'success', summary, detail })
  }

  const showError = (detail, summary = 'Operation echouee') => {
    showToast({ severity: 'error', summary, detail })
  }

  const showInfo = (detail, summary = 'Information') => {
    showToast({ severity: 'info', summary, detail })
  }

  const confirmRemoval = ({ message, header, onAccept }) => {
    if (!confirm) {
      const accepted = typeof window !== 'undefined'
        ? window.confirm([header || 'Confirmer la suppression', message].filter(Boolean).join('\n\n'))
        : true

      if (!accepted) {
        return
      }

      Promise.resolve(onAccept()).catch((error) => {
        showError(error?.message || 'La suppression a echoue.')
      })

      return
    }

    confirm.require({
      message,
      header: header || 'Confirmer la suppression',
      icon: 'pi pi-exclamation-triangle',
      rejectProps: {
        label: 'Annuler',
        severity: 'secondary',
        outlined: true
      },
      acceptProps: {
        label: 'Supprimer',
        severity: 'danger'
      },
      accept: async () => {
        await onAccept()
      }
    })
  }

  return {
    showToast,
    showSuccess,
    showError,
    showInfo,
    confirmRemoval
  }
}