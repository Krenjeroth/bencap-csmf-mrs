<script setup lang="ts">
/** Shows a one-time password once, with a copy button. */
const props = defineProps<{ password: string, email: string }>()
const toast = useToast()

async function copy() {
  try {
    await navigator.clipboard.writeText(props.password)
    toast.add({ title: 'Password copied', color: 'success', icon: 'i-lucide-copy-check' })
  }
  catch {
    toast.add({ title: 'Copy failed. Select the password and copy it by hand.', color: 'warning' })
  }
}
</script>

<template>
  <UAlert
    color="warning"
    variant="subtle"
    icon="i-lucide-key-round"
    title="Give this temporary password to the user"
  >
    <template #description>
      <div class="flex flex-col gap-3">
        <p>
          It is shown only once. {{ props.email }} must change it the first time they sign in.
        </p>
        <div class="flex flex-wrap items-center gap-2">
          <code
            class="select-all rounded bg-default px-3 py-1.5 font-mono text-base tracking-wide text-highlighted"
            data-testid="temporary-password"
          >{{ props.password }}</code>
          <UButton
            size="sm"
            color="neutral"
            variant="outline"
            icon="i-lucide-copy"
            @click="copy"
          >
            Copy
          </UButton>
        </div>
      </div>
    </template>
  </UAlert>
</template>
