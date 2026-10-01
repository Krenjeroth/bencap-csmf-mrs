<script setup lang="ts">
/**
 * Confirmation step for actions that change or remove data. The page owns
 * the action; this only asks, and shows the API's answer if it refuses.
 */
const props = withDefaults(defineProps<{
  title: string
  description: string
  confirmLabel: string
  danger?: boolean
  busy?: boolean
  error?: string | null
}>(), { danger: false, busy: false, error: null })

const open = defineModel<boolean>('open', { required: true })
const emit = defineEmits<{ confirm: [] }>()
</script>

<template>
  <UModal
    v-model:open="open"
    :title="props.title"
    :description="props.description"
  >
    <template
      v-if="props.error || $slots.default"
      #body
    >
      <div class="flex flex-col gap-4">
        <slot />
        <UAlert
          v-if="props.error"
          color="error"
          variant="subtle"
          icon="i-lucide-circle-alert"
          :title="props.error"
        />
      </div>
    </template>
    <template #footer>
      <div class="flex w-full justify-end gap-2">
        <UButton
          color="neutral"
          variant="outline"
          @click="open = false"
        >
          Cancel
        </UButton>
        <UButton
          :color="props.danger ? 'error' : 'primary'"
          :loading="props.busy"
          @click="emit('confirm')"
        >
          {{ props.confirmLabel }}
        </UButton>
      </div>
    </template>
  </UModal>
</template>
