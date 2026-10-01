import type { Paginated } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

export interface AdminListQuery {
  q: string
  page: number
  per_page: number
  sort: string
  [filter: string]: string | number | undefined
}

/**
 * Paged, searchable list for an admin endpoint. Search is debounced; any
 * other filter change reloads at once and returns to page 1.
 */
export function useAdminList<T>(endpoint: string, initial: Partial<AdminListQuery> = {}) {
  const client = useSanctumClient()

  const query = reactive<AdminListQuery>({ q: '', page: 1, per_page: 15, sort: '', ...initial })
  const rows = ref<T[]>([]) as Ref<T[]>
  const total = ref(0)
  const loading = ref(false)
  const error = ref<string | null>(null)

  let requestId = 0

  async function load() {
    const id = ++requestId
    loading.value = true
    error.value = null
    try {
      const params = Object.fromEntries(
        Object.entries(query).filter(([, value]) => value !== '' && value !== undefined),
      )
      const response = await client<Paginated<T>>(endpoint, { params })
      // Ignore answers to requests that a newer one has replaced.
      if (id !== requestId) {
        return
      }
      rows.value = response.data
      total.value = response.meta.total
    }
    catch (e) {
      if (id === requestId) {
        error.value = parseApiError(e).message
      }
    }
    finally {
      if (id === requestId) {
        loading.value = false
      }
    }
  }

  /** Back to page 1; the page watcher reloads, or reload here if already there. */
  function restart() {
    if (query.page !== 1) {
      query.page = 1
    }
    else {
      load()
    }
  }

  let searchTimer: ReturnType<typeof setTimeout> | undefined
  watch(() => query.q, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(restart, 300)
  })

  watch(
    () => Object.entries(query).filter(([key]) => key !== 'q' && key !== 'page').map(([, v]) => v).join('|'),
    restart,
  )

  watch(() => query.page, load)

  onBeforeUnmount(() => clearTimeout(searchTimer))

  return { query, rows, total, loading, error, load }
}
