import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'

const { clientMock } = vi.hoisted(() => ({ clientMock: vi.fn() }))
mockNuxtImport('useSanctumClient', () => () => clientMock)

function page(rows: { id: number }[], total = rows.length) {
  return { data: rows, links: {}, meta: { current_page: 1, from: 1, last_page: 1, per_page: 15, to: rows.length, total } }
}

beforeEach(() => {
  vi.useRealTimers()
  clientMock.mockReset()
})

describe('useAdminList', () => {
  it('loads rows and the total', async () => {
    clientMock.mockResolvedValue(page([{ id: 1 }, { id: 2 }], 42))
    const list = useAdminList<{ id: number }>('/api/v1/admin/users', { sort: 'name' })

    await list.load()

    expect(list.rows.value).toHaveLength(2)
    expect(list.total.value).toBe(42)
    expect(clientMock).toHaveBeenCalledWith('/api/v1/admin/users', { params: { page: 1, per_page: 15, sort: 'name' } })
  })

  it('leaves empty filters out of the request', async () => {
    clientMock.mockResolvedValue(page([]))
    const list = useAdminList('/api/v1/admin/users', { role_id: undefined })

    await list.load()

    expect(clientMock.mock.calls[0]?.[1]?.params).not.toHaveProperty('role_id')
    expect(clientMock.mock.calls[0]?.[1]?.params).not.toHaveProperty('q')
  })

  it('returns to page 1 when a filter changes', async () => {
    clientMock.mockResolvedValue(page([]))
    const list = useAdminList('/api/v1/admin/users', { status: undefined })
    list.query.page = 3
    await flushPromises()

    list.query.status = 'inactive'
    await flushPromises()

    expect(list.query.page).toBe(1)
    expect(clientMock).toHaveBeenLastCalledWith('/api/v1/admin/users', { params: expect.objectContaining({ page: 1, status: 'inactive' }) })
  })

  it('waits for typing to pause before searching', async () => {
    vi.useFakeTimers()
    clientMock.mockResolvedValue(page([]))
    const list = useAdminList('/api/v1/admin/users')

    list.query.q = 'ma'
    await nextTick()
    list.query.q = 'maria'
    await nextTick()
    expect(clientMock).not.toHaveBeenCalled()

    vi.advanceTimersByTime(300)
    await flushPromises()

    expect(clientMock).toHaveBeenCalledTimes(1)
    expect(clientMock).toHaveBeenCalledWith('/api/v1/admin/users', { params: expect.objectContaining({ q: 'maria' }) })
  })

  it('reports a failed load', async () => {
    clientMock.mockRejectedValue({ statusCode: 403, data: { message: 'This action is unauthorized.' } })
    const list = useAdminList('/api/v1/admin/users')

    await list.load()

    expect(list.error.value).toBe('This action is unauthorized.')
    expect(list.loading.value).toBe(false)
  })

  it('ignores an older answer that arrives after a newer one', async () => {
    let resolveFirst: (value: unknown) => void = () => {}
    clientMock
      .mockImplementationOnce(() => new Promise((resolve) => { resolveFirst = resolve }))
      .mockResolvedValueOnce(page([{ id: 2 }]))
    const list = useAdminList<{ id: number }>('/api/v1/admin/users')

    const first = list.load()
    await list.load()
    resolveFirst(page([{ id: 1 }]))
    await first

    expect(list.rows.value).toEqual([{ id: 2 }])
  })
})
