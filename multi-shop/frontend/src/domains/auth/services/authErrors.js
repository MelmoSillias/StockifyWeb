const LOGIN_FIELD_NAMES = ['email', 'password']

const getErrorMessage = (value) => {
  if (Array.isArray(value)) {
    return value.find((item) => typeof item === 'string' && item.trim()) || null
  }

  return typeof value === 'string' && value.trim() ? value : null
}

const extractFieldErrors = (payload) => {
  if (!payload || typeof payload !== 'object') {
    return {}
  }

  const fieldErrors = {}

  if (payload.errors && typeof payload.errors === 'object') {
    Object.entries(payload.errors).forEach(([field, value]) => {
      const message = getErrorMessage(value)

      if (message) {
        fieldErrors[field] = message
      }
    })
  }

  if (Array.isArray(payload.violations)) {
    payload.violations.forEach((violation) => {
      const field = violation.propertyPath || violation.field
      const message = violation.message

      if (field && message && !fieldErrors[field]) {
        fieldErrors[field] = message
      }
    })
  }

  return fieldErrors
}

export const validateLoginCredentials = (credentials = {}, loginMethod = 'email') => {
  const fieldErrors = {}
  const email = typeof credentials.email === 'string' ? credentials.email.trim() : ''
  const password = typeof credentials.password === 'string' ? credentials.password.trim() : ''

  if (!email) {
    fieldErrors.email = loginMethod === 'username'
      ? "Le nom d'utilisateur est obligatoire."
      : "L'adresse e-mail est obligatoire."
  }

  if (!password) {
    fieldErrors.password = 'Le mot de passe est obligatoire.'
  }

  return {
    valid: Object.keys(fieldErrors).length === 0,
    fieldErrors,
  }
}

export const normalizeAuthError = (error, context = 'login') => {
  if (error?.fieldErrors || error?.general) {
    return {
      fieldErrors: error.fieldErrors || {},
      general: error.general || null,
      toast: error.toast || null,
    }
  }

  if (typeof error?.message === 'string' && error.message.trim() && !error?.response) {
    return {
      fieldErrors: {},
      general: error.message,
      toast: {
        severity: 'error',
        summary: 'Echec de connexion',
        detail: error.message,
        life: 5000,
      },
    }
  }

  if (!error?.response) {
    return {
      fieldErrors: {},
      general: 'Impossible de contacter le serveur. Verifiez votre connexion puis reessayez.',
      toast: {
        severity: 'error',
        summary: 'Connexion indisponible',
        detail: 'Impossible de contacter le serveur. Verifiez votre connexion puis reessayez.',
        life: 5000,
      },
    }
  }

  const { status, data } = error.response
  const fieldErrors = extractFieldErrors(data)
  let general = getErrorMessage(data?.message) || getErrorMessage(data?.error)

  if (status === 400 && !general) {
    general = 'La requete de connexion est invalide.'
  }

  if (status === 401 && context === 'login') {
    const unauthorizedMessage = general || 'Identifiants incorrects.'

    return {
      fieldErrors,
      general: unauthorizedMessage,
      toast: null,
    }
  }

  if (status === 403 && !general) {
    general = 'Votre compte ne permet pas d acceder a cette application.'
  }

  if (status === 404 && !general) {
    general = 'Le service d authentification est introuvable.'
  }

  if (status === 422 && !general) {
    general = 'Certaines informations de connexion sont invalides.'
  }

  if (status === 429 && !general) {
    general = 'Trop de tentatives de connexion. Reessayez dans quelques instants.'
  }

  if (status >= 500 && !general) {
    general = 'Le serveur a rencontre une erreur. Reessayez plus tard.'
  }

  const fallbackMessage = general || 'Une erreur d authentification est survenue.'

  return {
    fieldErrors,
    general: fallbackMessage,
    toast: {
      severity: 'error',
      summary: 'Echec de connexion',
      detail: fallbackMessage,
      life: 5000,
    },
  }
}

export const clearAuthFieldError = (errors, fieldName) => {
  if (!LOGIN_FIELD_NAMES.includes(fieldName)) {
    return errors
  }

  const nextErrors = { ...errors }
  delete nextErrors[fieldName]

  if (LOGIN_FIELD_NAMES.every((field) => !nextErrors[field])) {
    delete nextErrors.general
  }

  return nextErrors
}
