import { defineVitestConfig } from '@nuxt/test-utils/config'

export default defineVitestConfig({
  test: {
    include: ['tests/**/*.spec.ts'],
    environment: 'nuxt',
    environmentOptions: {
      nuxt: {
        domEnvironment: 'happy-dom',
        overrides: {
          // Relative base so registerEndpoint() can answer API calls in tests.
          runtimeConfig: { public: { apiBase: '/api/v1' } },
        },
      },
    },
  },
})
