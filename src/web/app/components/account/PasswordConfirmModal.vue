<script setup lang="ts">
const { open, error, busy, submitPassword, cancel } = usePasswordConfirmation()
const password = ref('')

watch(open, (isOpen) => {
  if (!isOpen) {
    password.value = ''
  }
})

function onOpenChange(value: boolean) {
  if (!value) {
    cancel()
  }
}
</script>

<template>
  <UModal
    :open="open"
    title="Confirm your password"
    description="For your security, enter your password to continue."
    @update:open="onOpenChange"
  >
    <template #body>
      <form
        id="password-confirm-form"
        class="flex flex-col gap-4"
        @submit.prevent="submitPassword(password)"
      >
        <UFormField
          label="Password"
          :error="error ?? undefined"
        >
          <UInput
            id="password-confirm"
            v-model="password"
            type="password"
            autocomplete="current-password"
            class="w-full"
            autofocus
          />
        </UFormField>
      </form>
    </template>
    <template #footer>
      <div class="flex w-full justify-end gap-2">
        <UButton
          color="neutral"
          variant="outline"
          @click="cancel"
        >
          Cancel
        </UButton>
        <UButton
          type="submit"
          form="password-confirm-form"
          :loading="busy"
          :disabled="password === ''"
        >
          Confirm
        </UButton>
      </div>
    </template>
  </UModal>
</template>
