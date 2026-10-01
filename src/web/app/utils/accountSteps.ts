import type { Me } from '~/types/api'

export const PASSWORD_STEP = '/account/password'
export const TWO_FACTOR_STEP = '/account/security'

/**
 * The page a signed-in user must visit before using the rest of the app:
 * first replace a temporary password, then (System Administrators) turn on
 * two-factor login. Mirrors the API's password.changed and
 * two-factor.enforced middleware. Returns null when nothing is pending.
 */
export function pendingAccountStep(user: Pick<Me, 'must_change_password' | 'two_factor_required' | 'two_factor_enabled'> | null): string | null {
  if (!user) {
    return null
  }
  if (user.must_change_password) {
    return PASSWORD_STEP
  }
  if (user.two_factor_required && !user.two_factor_enabled) {
    return TWO_FACTOR_STEP
  }
  return null
}

/**
 * Only same-app paths are accepted as a post-login redirect, so a crafted
 * ?redirect=https://evil.example link cannot send users off-site.
 */
export function safeRedirect(target: unknown, fallback = '/'): string {
  if (typeof target !== 'string' || !target.startsWith('/') || target.startsWith('//') || target.startsWith('/\\')) {
    return fallback
  }
  return target
}
