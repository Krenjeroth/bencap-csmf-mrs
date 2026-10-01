import { describe, expect, it, vi } from 'vitest'
import { mockNuxtImport, mountSuspended, registerEndpoint } from '@nuxt/test-utils/runtime'
import { ref } from 'vue'
import { flushPromises } from '@vue/test-utils'
import type { Me } from '~/types/api'
import DefaultLayout from '~/layouts/default.vue'
import UserMenu from '~/components/app/UserMenu.vue'
import HomePage from '~/pages/index.vue'

// Components inside mountSuspended get their own copy of Nuxt state, so the
// signed-in user is provided by mocking useCurrentUser instead.
const { current } = vi.hoisted(() => ({ current: { user: null as Me | null } }))
mockNuxtImport('useCurrentUser', () => () => {
  const user = ref(current.user)
  const can = (p: string) => current.user?.is_system_administrator === true || (current.user?.permissions ?? []).includes(p)
  return { user, can, canAny: (list: string[]) => list.some(can) }
})

registerEndpoint('/api/v1/health', () => ({ status: 'ok', app: 'CSMF-MRS', database: 'ok', time: '2026-10-01T10:00:00+08:00' }))

function signIn(overrides: Partial<Me> = {}) {
  current.user = {
    id: '0199a3f2-0000-7000-8000-000000000002',
    name: 'Smoke Tester',
    email: 'smoke@csmf-mrs.test',
    is_active: true,
    must_change_password: false,
    two_factor_enabled: false,
    two_factor_required: false,
    is_system_administrator: false,
    last_login_at: null,
    created_at: null,
    updated_at: null,
    roles: [{ id: 3, title: 'Smoke Test', is_system: false }],
    permissions: ['users.view', 'roles.view'],
    ...overrides,
  }
}

describe('dashboard shell', () => {
  it('renders the user menu', async () => {
    signIn()
    const menu = await mountSuspended(UserMenu)
    expect(menu.text()).toContain('Smoke Tester')
  })

  it('renders the layout with only permitted links', async () => {
    signIn()
    const layout = await mountSuspended(DefaultLayout, { slots: { default: () => 'content' } })
    await flushPromises()

    expect(layout.text()).toContain('Users')
    expect(layout.text()).toContain('Roles')
    expect(layout.text()).not.toContain('Permissions')
  })

  it('renders the home page shortcuts', async () => {
    signIn()
    const page = await mountSuspended(HomePage)
    await flushPromises()

    expect(page.text()).toContain('Welcome, Smoke Tester')
  })
})
