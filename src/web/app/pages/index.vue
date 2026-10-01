<script setup lang="ts">
import type { ApiHealthState } from '~/composables/useApiHealth'

useHead({ title: 'CSMF-MRS' })

const { state, checkedAt, check } = useApiHealth()
onMounted(check)

const status: Record<ApiHealthState, { label: string, color: 'neutral' | 'success' | 'warning' | 'error', icon: string, hint: string }> = {
  checking: { label: 'Checking', color: 'neutral', icon: 'i-lucide-loader-circle', hint: 'Contacting the API.' },
  ok: { label: 'Online', color: 'success', icon: 'i-lucide-circle-check', hint: 'The API and database are reachable.' },
  degraded: { label: 'Database down', color: 'warning', icon: 'i-lucide-triangle-alert', hint: 'The API is running but cannot reach the database. Check that XAMPP\'s database server is started.' },
  unreachable: { label: 'Offline', color: 'error', icon: 'i-lucide-circle-x', hint: 'The API did not answer. Start it with: php artisan serve --host=csmf-mrs --port=8003' },
}

const current = computed(() => status[state.value])
</script>

<template>
  <main class="mx-auto flex min-h-dvh max-w-2xl flex-col justify-center gap-8 px-4 py-12">
    <header class="flex flex-col gap-2">
      <p class="text-xs font-semibold uppercase tracking-widest text-secondary">
        Provincial Government of Benguet
      </p>
      <h1 class="text-3xl font-bold text-highlighted text-balance">
        Client Satisfaction Measurement Form Management and Reporting System
      </h1>
      <p class="text-muted">
        Collects ARTA client satisfaction feedback from every office and turns it into summary reports.
      </p>
    </header>

    <UCard>
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
          <span class="text-sm font-medium text-highlighted">System status</span>
          <span
            class="text-sm text-muted"
            data-testid="health-hint"
          >{{ current.hint }}</span>
        </div>
        <UBadge
          :color="current.color"
          :icon="current.icon"
          variant="subtle"
          size="lg"
          data-testid="health-badge"
        >
          {{ current.label }}
        </UBadge>
      </div>
      <template #footer>
        <div class="flex items-center justify-between gap-4">
          <span class="text-xs text-dimmed tabular-nums">
            {{ checkedAt ? `Last checked ${checkedAt.toLocaleTimeString('en-PH')}` : 'Not checked yet' }}
          </span>
          <UButton
            icon="i-lucide-refresh-cw"
            color="neutral"
            variant="outline"
            size="sm"
            :loading="state === 'checking'"
            @click="check"
          >
            Check again
          </UButton>
        </div>
      </template>
    </UCard>
  </main>
</template>
