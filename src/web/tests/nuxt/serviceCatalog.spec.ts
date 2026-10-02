import { describe, expect, it, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { ref } from 'vue'
import { flushPromises } from '@vue/test-utils'
import type { Me, Office, Service } from '~/types/api'
import DefaultLayout from '~/layouts/default.vue'
import OfficesPage from '~/pages/admin/offices/index.vue'
import ServicesPage from '~/pages/admin/services/index.vue'
import ServiceTypesPage from '~/pages/admin/service-types/index.vue'

// Components inside mountSuspended get their own copy of Nuxt state, so the
// signed-in user is provided by mocking useCurrentUser instead.
const { current } = vi.hoisted(() => ({ current: { user: null as Me | null } }))
mockNuxtImport('useCurrentUser', () => () => {
  const user = ref(current.user)
  const can = (p: string) => current.user?.is_system_administrator === true || (current.user?.permissions ?? []).includes(p)
  return { user, can, canAny: (list: string[]) => list.some(can) }
})

const pho = { id: 21, code: 'PHO', name: 'Provincial Health Office' }

function page<T>(data: T[]) {
  return {
    data,
    links: { first: null, last: null, prev: null, next: null },
    meta: { current_page: 1, from: data.length ? 1 : null, last_page: 1, per_page: 15, to: data.length || null, total: data.length },
  }
}

const og = { id: 1, code: 'OG', name: 'Office of the Governor (OG)' }
const office = (fields: Partial<Office> & Pick<Office, 'id' | 'code' | 'name' | 'slug'>): Office => ({
  parent_id: null, parent: null, is_active: true, sort_order: 0, services_count: 0, active_services_count: 0,
  users_count: 0, children_count: 0, created_at: null, updated_at: null, ...fields,
})
const offices: Office[] = [
  office({ ...og, slug: 'og', sort_order: 1, services_count: 7, active_services_count: 7, children_count: 1 }),
  office({ id: 9, code: 'OG-BTS', name: 'Benguet Technical School (OG-BTS)', slug: 'og-bts', parent_id: og.id, parent: og, sort_order: 9 }),
  office({ ...pho, slug: 'pho', sort_order: 21, services_count: 8, active_services_count: 7, users_count: 2 }),
]
const services: Service[] = [{
  id: 501, name: 'Issuance of Medical Certificate', charter_year: 2026, is_active: true, sort_order: 1,
  office: pho, service_type: { id: 2, type: 'External' }, created_at: null, updated_at: null,
}]

const responses: Record<string, unknown> = {
  '/api/v1/admin/offices': page(offices),
  '/api/v1/admin/services': page(services),
  '/api/v1/admin/service-types': { data: [
    { id: 1, type: 'Internal', description: 'For PLGU offices and employees', services_count: 10, created_at: null, updated_at: null },
    { id: 2, type: 'External', description: null, services_count: 231, created_at: null, updated_at: null },
  ] },
  '/api/v1/admin/office-options': { data: [{ ...og, parent_id: null, is_active: true }, { ...pho, parent_id: null, is_active: true }] },
}

// Answers by endpoint; anything else is refused, as for a missing permission
// (service-type-options here), and the page must still render.
const { clientMock } = vi.hoisted(() => ({ clientMock: vi.fn() }))
mockNuxtImport('useSanctumClient', () => () => clientMock)
clientMock.mockImplementation(async (endpoint: string) => {
  if (endpoint in responses) {
    return responses[endpoint]
  }
  throw Object.assign(new Error('Forbidden'), { statusCode: 403 })
})

function signIn(overrides: Partial<Me> = {}) {
  current.user = {
    id: '0199a3f2-0000-7000-8000-000000000003',
    name: 'Catalog Tester',
    email: 'catalog@csmf-mrs.test',
    is_active: true,
    must_change_password: false,
    two_factor_enabled: false,
    two_factor_required: false,
    is_system_administrator: false,
    last_login_at: null,
    office: null,
    created_at: null,
    updated_at: null,
    roles: [{ id: 2, title: 'Admin', is_system: false }],
    permissions: ['offices.view', 'services.view', 'services.update'],
    ...overrides,
  }
}

describe('service catalog navigation', () => {
  it('shows only the catalog screens the user may open', async () => {
    signIn()
    const layout = await mountSuspended(DefaultLayout, { slots: { default: () => 'content' } })
    await flushPromises()

    expect(layout.text()).toContain('Service catalog')
    expect(layout.text()).toContain('Offices')
    expect(layout.text()).toContain('Services')
    expect(layout.text()).not.toContain('Service types')
  })

  it('drops the section when no catalog permission is held', async () => {
    signIn({ permissions: ['users.view'] })
    const layout = await mountSuspended(DefaultLayout, { slots: { default: () => 'content' } })
    await flushPromises()

    expect(layout.text()).not.toContain('Service catalog')
  })
})

describe('offices page', () => {
  it('lists offices with their guest form address and counts', async () => {
    signIn()
    const view = await mountSuspended(OfficesPage)
    await flushPromises()

    expect(view.text()).toContain('Provincial Health Office')
    expect(view.text()).toContain('/f/pho')
    expect(view.text()).toContain('7 of 8')
    expect(view.text()).toContain('3 offices')
    expect(view.text()).not.toContain('Add office')
  })

  it('loads every office on one page for the tree', async () => {
    clientMock.mockClear()
    signIn()
    await mountSuspended(OfficesPage)
    await flushPromises()

    const listCalls = clientMock.mock.calls.filter(([endpoint]) => endpoint === '/api/v1/admin/offices')
    expect(listCalls).toHaveLength(1)
    expect(listCalls[0]?.[1]?.params).toMatchObject({ per_page: 100, sort: 'sort_order' })
  })

  it('nests child offices under their parent and collapses them', async () => {
    signIn()
    const view = await mountSuspended(OfficesPage)
    await flushPromises()

    const codes = () => view.findAll('tbody tr').map(tr => tr.find('td').text())
    expect(codes()).toEqual(['OG1', 'OG-BTS', 'PHO'])

    await view.find('button[aria-label="Collapse OG"]').trigger('click')
    expect(codes()).toEqual(['OG1', 'PHO'])
    expect(view.find('button[aria-label="Expand OG"]').exists()).toBe(true)
  })
})

describe('services page', () => {
  it('lists services and lets an unlimited user filter by office', async () => {
    signIn()
    const view = await mountSuspended(ServicesPage)
    await flushPromises()

    expect(view.text()).toContain('Issuance of Medical Certificate')
    expect(view.text()).toContain('External')
    expect(view.find('#services-office-filter').exists()).toBe(true)
    expect(view.text()).not.toContain('Showing services of')
  })

  it('locks an office-limited user to their office', async () => {
    signIn({ office: pho })
    const view = await mountSuspended(ServicesPage)
    await flushPromises()

    expect(view.text()).toContain('Showing services of PHO only')
    expect(view.find('#services-office-filter').exists()).toBe(false)
  })

  it('does not lock a System Administrator who has an office', async () => {
    signIn({ office: pho, is_system_administrator: true })
    const view = await mountSuspended(ServicesPage)
    await flushPromises()

    expect(view.text()).not.toContain('Showing services of')
    expect(view.text()).toContain('Add service')
  })
})

describe('service types page', () => {
  it('lists the unpaged service types', async () => {
    signIn({ permissions: ['service-types.view'] })
    const view = await mountSuspended(ServiceTypesPage)
    await flushPromises()

    expect(view.text()).toContain('Internal')
    expect(view.text()).toContain('For PLGU offices and employees')
    expect(view.text()).toContain('231')
    expect(view.text()).not.toContain('Add service type')
  })
})

describe('useOptions', () => {
  it('fills a list the API allows', async () => {
    const { offices: list, loadOffices } = useOptions()
    await loadOffices()

    expect(list.value.map(o => o.code)).toEqual(['OG', 'PHO'])
  })

  it('leaves a refused list empty instead of throwing', async () => {
    const { serviceTypes, loadServiceTypes } = useOptions()
    serviceTypes.value = [{ id: 9, type: 'Stale' }]

    await expect(loadServiceTypes()).resolves.toBeUndefined()
    expect(serviceTypes.value).toEqual([])
  })
})
