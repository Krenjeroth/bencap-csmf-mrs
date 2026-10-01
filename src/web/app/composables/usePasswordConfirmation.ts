import { parseApiError } from '~/utils/apiError'

/**
 * The action waiting for the password. Module-level so the page that asks
 * and the modal that answers share it (the app is client-only, ssr: false).
 */
let pending: { resolve: () => void, reject: (reason: unknown) => void } | null = null

/**
 * Fortify asks for the password again (HTTP 423) before sensitive account
 * actions such as turning two-factor login on or off. `withConfirmation`
 * runs an action; on 423 it opens the password prompt, confirms the
 * password with the API, then retries the action once.
 */
export function usePasswordConfirmation() {
  const client = useSanctumClient()

  const open = useState('password-confirm-open', () => false)
  const error = useState<string | null>('password-confirm-error', () => null)
  const busy = useState('password-confirm-busy', () => false)

  function requestPassword(): Promise<void> {
    error.value = null
    open.value = true
    return new Promise((resolve, reject) => {
      pending = { resolve, reject }
    })
  }

  /** Called by the prompt's form. */
  async function submitPassword(password: string) {
    busy.value = true
    error.value = null
    try {
      await client('/api/user/confirm-password', { method: 'post', body: { password } })
      open.value = false
      pending?.resolve()
      pending = null
    }
    catch (e) {
      const info = parseApiError(e)
      error.value = info.fields.password ?? info.message
    }
    finally {
      busy.value = false
    }
  }

  function cancel() {
    open.value = false
    pending?.reject(new Error('Password confirmation cancelled'))
    pending = null
  }

  async function withConfirmation<T>(action: () => Promise<T>): Promise<T> {
    try {
      return await action()
    }
    catch (e) {
      if (parseApiError(e).status !== 423) {
        throw e
      }
      await requestPassword()
      return await action()
    }
  }

  return { open, error, busy, submitPassword, cancel, withConfirmation }
}
