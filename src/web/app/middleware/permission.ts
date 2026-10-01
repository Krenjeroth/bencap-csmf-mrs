declare module '#app' {
  interface PageMeta {
    /** Permission title required to open the page, e.g. "users.view". */
    permission?: string
  }
}

/**
 * Blocks pages whose `permission` meta the user lacks. Use after
 * sanctum:auth: definePageMeta({ middleware: ['sanctum:auth', 'permission'], permission: 'users.view' }).
 */
export default defineNuxtRouteMiddleware((to) => {
  const required = to.meta.permission
  if (!required) {
    return
  }

  const { can } = useCurrentUser()
  if (!can(required)) {
    return abortNavigation(createError({
      statusCode: 403,
      statusMessage: 'You do not have permission to open this page.',
    }))
  }
})
