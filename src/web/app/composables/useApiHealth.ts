export type ApiHealthState = 'checking' | 'ok' | 'degraded' | 'unreachable'

interface HealthResponse {
  status: 'ok' | 'degraded'
  app: string
  database: 'ok' | 'unreachable'
  time: string
}

/**
 * Asks the API's GET /health endpoint whether the API and its database are up.
 *
 * - 'ok': API and database reachable
 * - 'degraded': API answered 503 because the database is down
 * - 'unreachable': no usable answer (API down, network or CORS failure)
 *
 * @param fetcher HTTP client; defaults to Nuxt's $fetch. Tests pass a stub.
 */
export function useApiHealth(fetcher: typeof $fetch = $fetch) {
  const { apiBase } = useRuntimeConfig().public
  const state = ref<ApiHealthState>('checking')
  const checkedAt = ref<Date | null>(null)

  async function check(): Promise<ApiHealthState> {
    state.value = 'checking'
    try {
      const body = await fetcher<HealthResponse>(`${apiBase}/health`, {
        // A 503 still carries a JSON body worth reading.
        ignoreResponseError: true,
        timeout: 5000,
      })
      state.value = body?.status === 'ok' ? 'ok' : body?.status === 'degraded' ? 'degraded' : 'unreachable'
    }
    catch {
      state.value = 'unreachable'
    }
    checkedAt.value = new Date()
    return state.value
  }

  return { state: readonly(state), checkedAt: readonly(checkedAt), check }
}
