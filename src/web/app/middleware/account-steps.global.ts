import { pendingAccountStep, PASSWORD_STEP, TWO_FACTOR_STEP } from '~/utils/accountSteps'

/** Pages a user with a pending account step may still open. */
const ALWAYS_ALLOWED = new Set(['/login', '/two-factor-challenge', PASSWORD_STEP, TWO_FACTOR_STEP])

/**
 * Sends a signed-in user to their pending step (temporary password, then
 * two-factor for System Administrators) before any other page.
 */
export default defineNuxtRouteMiddleware((to) => {
  const { user } = useCurrentUser()
  const step = pendingAccountStep(user.value)

  if (step && !ALWAYS_ALLOWED.has(to.path) && to.path !== step) {
    // Password comes first: don't let the two-factor page skip it.
    return navigateTo(step, { replace: true })
  }
  if (step === PASSWORD_STEP && to.path === TWO_FACTOR_STEP) {
    return navigateTo(PASSWORD_STEP, { replace: true })
  }
})
