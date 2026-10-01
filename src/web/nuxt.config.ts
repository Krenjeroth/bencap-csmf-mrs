// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({

  modules: ['@nuxt/ui', '@nuxt/eslint', '@nuxt/test-utils/module', 'nuxt-auth-sanctum'],

  // Client-rendered SPA, same as PRJ-itsms: the production build is static
  // files served by Nginx next to the API, so no Node process runs there.
  ssr: false,
  devtools: { enabled: false },

  app: {
    head: {
      htmlAttrs: { lang: 'en' },
      title: 'CSMF-MRS',
      meta: [
        { name: 'description', content: 'Client Satisfaction Measurement Form Management and Reporting System' },
        { name: 'author', content: 'Krenjer Jan J. Sapitola' },
      ],
    },
  },

  css: ['~/assets/css/main.css'],

  colorMode: {
    preference: 'system',
    fallback: 'light',
  },

  runtimeConfig: {
    public: {
      // Overridden by NUXT_PUBLIC_API_BASE (see .env.example).
      apiBase: 'http://csmf-mrs:8003/api/v1',
    },
  },

  // Pinned per workspace CLAUDE.md: csmf-mrs:8030 (web), csmf-mrs:8003 (API).
  devServer: {
    host: 'csmf-mrs',
    port: 8030,
  },
  compatibilityDate: '2026-01-01',

  eslint: {
    config: {
      stylistic: true,
    },
  },

  sanctum: {
    // Overridden by NUXT_PUBLIC_SANCTUM_BASE_URL.
    baseUrl: 'http://csmf-mrs:8003',
    mode: 'cookie',
    client: {
      // Sprint 1 adds Fortify login and GET /api/v1/me; until then there is
      // no user endpoint to ask on page load.
      initialRequest: false,
    },
    endpoints: {
      user: '/api/v1/me',
    },
  },
})
