import type { Me } from '~/types/api'

/**
 * The signed-in user (from GET /api/v1/me) and permission checks for
 * showing or hiding screens. The API enforces the same permissions; this
 * only decides what to display.
 */
export function useCurrentUser() {
  const user = useSanctumUser<Me>()

  const permissions = computed(() => new Set(user.value?.permissions ?? []))

  function can(permission: string): boolean {
    return user.value?.is_system_administrator === true || permissions.value.has(permission)
  }

  function canAny(list: string[]): boolean {
    return list.some(can)
  }

  return { user, can, canAny }
}
