import { defineVitestConfig } from '@nuxt/test-utils/config'

export default defineVitestConfig({
  test: {
    include: ['tests/**/*.spec.ts'],
    environment: 'nuxt',
    // Booting the Nuxt test environment for several files in parallel can
    // take longer than Vitest's 10 s default on Windows.
    hookTimeout: 60_000,
    testTimeout: 20_000,
    environmentOptions: {
      nuxt: {
        domEnvironment: 'happy-dom',
        overrides: {
          // Relative base so registerEndpoint() can answer API calls in tests.
          runtimeConfig: { public: { apiBase: '/api/v1' } },
          // Tests set the signed-in user themselves; don't let the plugin's
          // start-up GET /api/v1/me (unanswered in tests) clear it.
          sanctum: { client: { initialRequest: false } },
        },
      },
    },
  },
})
