import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mockNuxtImport, mountSuspended, registerEndpoint } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import LoginPage from '~/pages/login.vue'

const { signInMock } = vi.hoisted(() => ({ signInMock: vi.fn() }))

mockNuxtImport('useSignIn', () => () => ({
  signIn: signInMock,
  verifyTwoFactor: vi.fn(),
  continueAfterSignIn: vi.fn(),
  signOut: vi.fn(),
}))

registerEndpoint('/api/v1/health', () => ({ status: 'ok', app: 'CSMF-MRS', database: 'ok', time: '2026-10-01T10:00:00+08:00' }))

async function fillAndSubmit(page: Awaited<ReturnType<typeof mountSuspended>>, email: string, password: string) {
  await page.get('#login-email').setValue(email)
  await page.get('#login-password').setValue(password)
  await page.get('form').trigger('submit')
  await flushPromises()
}

beforeEach(() => {
  signInMock.mockReset()
})

describe('login page', () => {
  it('signs in with the typed email and password', async () => {
    signInMock.mockResolvedValue(false)
    const page = await mountSuspended(LoginPage)

    await fillAndSubmit(page, 'clerk@benguet.gov.ph', 'Test-Password-2026!')

    expect(signInMock).toHaveBeenCalledWith('clerk@benguet.gov.ph', 'Test-Password-2026!', false)
  })

  it('shows the API message and clears the password when sign-in fails', async () => {
    signInMock.mockRejectedValue({ statusCode: 422, data: { errors: { email: ['These credentials do not match our records.'] } } })
    const page = await mountSuspended(LoginPage)

    await fillAndSubmit(page, 'clerk@benguet.gov.ph', 'Wrong-Password-1!')

    expect(page.get('[data-testid="login-error"]').text()).toContain('These credentials do not match our records.')
    expect((page.get('#login-password').element as HTMLInputElement).value).toBe('')
  })

  it('explains the lockout after too many attempts', async () => {
    signInMock.mockRejectedValue({ statusCode: 429, data: { message: 'Too Many Attempts.' } })
    const page = await mountSuspended(LoginPage)

    await fillAndSubmit(page, 'clerk@benguet.gov.ph', 'Wrong-Password-1!')

    expect(page.get('[data-testid="login-error"]').text()).toContain('Too Many Attempts.')
  })

  it('does not call the API with an invalid email', async () => {
    const page = await mountSuspended(LoginPage)

    await fillAndSubmit(page, 'not-an-email', 'whatever')

    expect(signInMock).not.toHaveBeenCalled()
    expect(page.text()).toContain('Enter a valid email address.')
  })

  it('tells staff how to recover a forgotten password', async () => {
    const page = await mountSuspended(LoginPage)

    expect(page.text()).toContain('Ask your System Administrator to reset it.')
  })
})
