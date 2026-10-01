import { describe, expect, it, vi } from 'vitest'
import { mountSuspended, registerEndpoint } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import IndexPage from '~/pages/index.vue'

const OK_BODY = { status: 'ok', app: 'CSMF-MRS', database: 'ok', time: '2026-10-01T10:00:00+08:00' }

// registerEndpoint replaces the previous handler for the same path, so each
// test sets the answer it needs.
function answerHealth(status: number, body: unknown) {
  registerEndpoint('/api/v1/health', {
    method: 'GET',
    handler: (event) => {
      event.node.res.statusCode = status
      return body
    },
  })
}

describe('useApiHealth', () => {
  it('starts in the checking state with no timestamp', () => {
    const { state, checkedAt } = useApiHealth()

    expect(state.value).toBe('checking')
    expect(checkedAt.value).toBeNull()
  })

  it('reports ok when the API and database answer', async () => {
    answerHealth(200, OK_BODY)
    const { state, checkedAt, check } = useApiHealth()

    await expect(check()).resolves.toBe('ok')
    expect(state.value).toBe('ok')
    expect(checkedAt.value).toBeInstanceOf(Date)
  })

  it('reports degraded when the API answers 503 because the database is down', async () => {
    answerHealth(503, { ...OK_BODY, status: 'degraded', database: 'unreachable' })

    await expect(useApiHealth().check()).resolves.toBe('degraded')
  })

  it('reports unreachable when the response is not a health payload', async () => {
    answerHealth(200, { unexpected: true })

    await expect(useApiHealth().check()).resolves.toBe('unreachable')
  })

  it('reports unreachable when the request fails outright', async () => {
    const failing = vi.fn().mockRejectedValue(new TypeError('Failed to fetch')) as unknown as typeof $fetch
    const { state, checkedAt, check } = useApiHealth(failing)

    await expect(check()).resolves.toBe('unreachable')
    expect(state.value).toBe('unreachable')
    expect(checkedAt.value).toBeInstanceOf(Date)
  })

  it('calls the configured API base with a timeout', async () => {
    const fetcher = vi.fn().mockResolvedValue(OK_BODY) as unknown as typeof $fetch

    await useApiHealth(fetcher).check()

    expect(fetcher).toHaveBeenCalledWith('/api/v1/health', expect.objectContaining({ timeout: 5000, ignoreResponseError: true }))
  })
})

describe('index page', () => {
  it('shows Online once the health check succeeds', async () => {
    answerHealth(200, OK_BODY)

    const page = await mountSuspended(IndexPage)
    await flushPromises()

    expect(page.get('[data-testid="health-badge"]').text()).toBe('Online')
    expect(page.text()).toContain('Client Satisfaction Measurement Form Management and Reporting System')
  })

  it('tells the user how to start the API when it does not answer properly', async () => {
    answerHealth(502, '<html><body>Bad Gateway</body></html>')

    const page = await mountSuspended(IndexPage)
    await flushPromises()

    expect(page.get('[data-testid="health-badge"]').text()).toBe('Offline')
    expect(page.get('[data-testid="health-hint"]').text()).toContain('php artisan serve --host=csmf-mrs --port=8003')
  })
})
