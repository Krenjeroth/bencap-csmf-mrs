import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import type { RouteLocationNormalized } from 'vue-router'
import type { Me } from '~/types/api'
import permissionMiddleware from '~/middleware/permission'
import accountSteps from '~/middleware/account-steps.global'

const { navigateToMock, abortNavigationMock } = vi.hoisted(() => ({
  navigateToMock: vi.fn((to: unknown) => ({ redirectedTo: to })),
  abortNavigationMock: vi.fn((error: unknown) => ({ aborted: error })),
}))

mockNuxtImport('navigateTo', () => navigateToMock)
mockNuxtImport('abortNavigation', () => abortNavigationMock)

function me(overrides: Partial<Me> = {}): Me {
  return {
    id: '0199a3f2-0000-7000-8000-000000000001',
    name: 'Office Admin',
    email: 'admin@benguet.gov.ph',
    is_active: true,
    must_change_password: false,
    two_factor_enabled: false,
    two_factor_required: false,
    is_system_administrator: false,
    last_login_at: null,
    created_at: null,
    updated_at: null,
    roles: [{ id: 2, title: 'Admin', is_system: false }],
    permissions: ['dashboard.view', 'reports.view'],
    ...overrides,
  }
}

function route(path: string, meta: Record<string, unknown> = {}) {
  return { path, meta } as unknown as RouteLocationNormalized
}

beforeEach(() => {
  navigateToMock.mockClear()
  abortNavigationMock.mockClear()
  useSanctumUser<Me>().value = null
})

describe('useCurrentUser().can', () => {
  it('allows only the permissions the user holds', () => {
    useSanctumUser<Me>().value = me()
    const { can, canAny } = useCurrentUser()

    expect(can('reports.view')).toBe(true)
    expect(can('users.view')).toBe(false)
    expect(canAny(['users.view', 'dashboard.view'])).toBe(true)
  })

  it('allows everything for a System Administrator', () => {
    useSanctumUser<Me>().value = me({ is_system_administrator: true, permissions: [] })

    expect(useCurrentUser().can('anything.at-all')).toBe(true)
  })

  it('allows nothing when signed out', () => {
    expect(useCurrentUser().can('dashboard.view')).toBe(false)
  })
})

describe('permission middleware', () => {
  it('lets the page open when the user has the permission', () => {
    useSanctumUser<Me>().value = me()

    expect(permissionMiddleware(route('/reports', { permission: 'reports.view' }), route('/'))).toBeUndefined()
    expect(abortNavigationMock).not.toHaveBeenCalled()
  })

  it('refuses with 403 when the permission is missing', () => {
    useSanctumUser<Me>().value = me()

    permissionMiddleware(route('/admin/users', { permission: 'users.view' }), route('/'))

    expect(abortNavigationMock).toHaveBeenCalledWith(expect.objectContaining({ statusCode: 403 }))
  })

  it('ignores pages without a permission requirement', () => {
    expect(permissionMiddleware(route('/'), route('/'))).toBeUndefined()
  })
})

describe('account-steps middleware', () => {
  it('sends a user with a temporary password to the password page', () => {
    useSanctumUser<Me>().value = me({ must_change_password: true })

    accountSteps(route('/admin/users'), route('/'))

    expect(navigateToMock).toHaveBeenCalledWith('/account/password', { replace: true })
  })

  it('keeps the password step ahead of the two-factor page', () => {
    useSanctumUser<Me>().value = me({ must_change_password: true, two_factor_required: true })

    accountSteps(route('/account/security'), route('/'))

    expect(navigateToMock).toHaveBeenCalledWith('/account/password', { replace: true })
  })

  it('sends a System Administrator without two-factor to the security page', () => {
    useSanctumUser<Me>().value = me({ two_factor_required: true, is_system_administrator: true })

    accountSteps(route('/'), route('/login'))

    expect(navigateToMock).toHaveBeenCalledWith('/account/security', { replace: true })
  })

  it('leaves a fully set-up user alone', () => {
    useSanctumUser<Me>().value = me()

    accountSteps(route('/admin/users'), route('/'))

    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('leaves signed-out visitors alone', () => {
    accountSteps(route('/login'), route('/'))

    expect(navigateToMock).not.toHaveBeenCalled()
  })
})
