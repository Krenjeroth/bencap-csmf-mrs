import type { Me } from '~/types/api'
import { pendingAccountStep, safeRedirect } from '~/utils/accountSteps'

interface LoginResponse {
  two_factor?: boolean
}

/**
 * Sign-in flow against Fortify (POST /api/login, /api/two-factor-challenge).
 *
 * nuxt-auth-sanctum's login() is called without its own identity fetch and
 * redirect, because a user with two-factor on is not signed in until the
 * second step succeeds.
 */
export function useSignIn() {
  const { login, refreshIdentity, logout } = useSanctumAuth()
  const client = useSanctumClient()
  const route = useRoute()

  /** Where to go once fully signed in: a pending step, ?redirect=, or home. */
  async function continueAfterSignIn() {
    await refreshIdentity()
    const user = useSanctumUser<Me>().value
    const target = pendingAccountStep(user) ?? safeRedirect(route.query.redirect)
    await navigateTo(target, { replace: true })
  }

  /** @returns true when a two-factor code is needed next. */
  async function signIn(email: string, password: string, remember: boolean): Promise<boolean> {
    const response = (await login({ email, password, remember }, false)) as LoginResponse | undefined

    if (response?.two_factor) {
      await navigateTo({ path: '/two-factor-challenge', query: route.query }, { replace: true })
      return true
    }

    await continueAfterSignIn()
    return false
  }

  async function verifyTwoFactor(input: { code: string } | { recovery_code: string }) {
    await client('/api/two-factor-challenge', { method: 'post', body: input })
    await continueAfterSignIn()
  }

  async function signOut() {
    await logout()
  }

  return { signIn, verifyTwoFactor, continueAfterSignIn, signOut }
}
