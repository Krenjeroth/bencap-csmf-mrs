/** A failed API call reduced to what a form needs to show. */
export interface ApiErrorInfo {
  status: number | null
  /** Human-readable summary, safe to show in a toast or alert. */
  message: string
  /** First message per field, keyed like the request body (e.g. "role_ids"). */
  fields: Record<string, string>
  /** Machine-readable code from the API, e.g. "two_factor_required". */
  code: string | null
}

interface FetchLikeError {
  statusCode?: number
  status?: number
  response?: { status?: number }
  data?: { message?: unknown, errors?: Record<string, unknown>, code?: unknown }
}

const FALLBACKS: Record<number, string> = {
  401: 'Your session has ended. Sign in again.',
  403: 'You do not have permission to do that.',
  404: 'That record no longer exists.',
  419: 'The page expired. Reload and try again.',
  429: 'Too many attempts. Wait a minute and try again.',
}

/** Turns an ofetch/Laravel error into messages for the UI. */
export function parseApiError(error: unknown): ApiErrorInfo {
  const e = (error ?? {}) as FetchLikeError
  const status = e.statusCode ?? e.status ?? e.response?.status ?? null
  const data = e.data ?? {}

  const fields: Record<string, string> = {}
  for (const [key, value] of Object.entries(data.errors ?? {})) {
    const first = Array.isArray(value) ? value[0] : value
    if (typeof first === 'string') {
      fields[key] = first
    }
  }

  const apiMessage = typeof data.message === 'string' ? data.message : null
  let message: string
  if (status === 422 && Object.keys(fields).length > 0) {
    message = Object.values(fields)[0] as string
  }
  else if (apiMessage && status !== 500) {
    message = apiMessage
  }
  else if (status && FALLBACKS[status]) {
    message = FALLBACKS[status]
  }
  else if (status === null) {
    message = 'Could not reach the server. Check your connection and try again.'
  }
  else {
    message = 'Something went wrong on the server. Try again, and report it if it keeps happening.'
  }

  return {
    status,
    message,
    fields,
    code: typeof data.code === 'string' ? data.code : null,
  }
}
