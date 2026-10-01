# CSMF-MRS Web

Nuxt 4 + Nuxt UI 4 web app for CSMF-MRS: the admin dashboard (Sprint 1+)
and the public guest feedback form (Sprint 3). Client-rendered SPA
(`ssr: false`), same as PRJ-itsms. See the [project README](../../README.md)
for setup.

```
npm run dev         # http://csmf-mrs:8030
npm test            # Vitest (tests/)
npm run lint        # ESLint
npm run typecheck   # vue-tsc
npm run build       # production build
```

API base URLs default to the pinned dev ports in `nuxt.config.ts`.
Override them with `NUXT_PUBLIC_API_BASE` and `NUXT_PUBLIC_SANCTUM_BASE_URL`
(see `.env.example`).
