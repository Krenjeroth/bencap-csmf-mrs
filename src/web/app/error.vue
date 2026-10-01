<script setup lang="ts">
import type { NuxtError } from '#app'

const props = defineProps<{ error: NuxtError }>()

const copy = computed(() => {
  switch (props.error.statusCode) {
    case 403:
      return { title: 'You do not have access to this page', body: 'Ask your System Administrator if you need it for your work.' }
    case 404:
      return { title: 'Page not found', body: 'The address may be mistyped, or the page has moved.' }
    default:
      return { title: 'Something went wrong', body: 'Try again. If it keeps happening, tell your System Administrator what you were doing.' }
  }
})

useHead({ title: `${copy.value.title} · CSMF-MRS` })
</script>

<template>
  <UApp>
    <main class="mx-auto flex min-h-dvh max-w-lg flex-col justify-center gap-4 px-4 py-12">
      <p class="text-sm font-semibold tabular-nums text-secondary">
        Error {{ error.statusCode }}
      </p>
      <h1 class="text-2xl font-semibold text-highlighted text-balance">
        {{ copy.title }}
      </h1>
      <p class="text-muted">
        {{ copy.body }}
      </p>
      <div>
        <UButton
          icon="i-lucide-house"
          @click="clearError({ redirect: '/' })"
        >
          Go to home
        </UButton>
      </div>
    </main>
  </UApp>
</template>
