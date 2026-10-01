<script setup lang="ts">
import { parseApiError } from '~/utils/apiError'

definePageMeta({ layout: 'auth', middleware: ['sanctum:guest'] })
useHead({ title: 'Two-factor code · CSMF-MRS' })

const { verifyTwoFactor } = useSignIn()

const useRecovery = ref(false)
const code = ref<string[]>([])
const recoveryCode = ref('')
const submitting = ref(false)
const errorMessage = ref<string | null>(null)

const codeComplete = computed(() => code.value.join('').length === 6)

async function submit() {
  if (useRecovery.value ? recoveryCode.value.trim() === '' : !codeComplete.value) {
    errorMessage.value = useRecovery.value ? 'Enter one of your recovery codes.' : 'Enter the 6-digit code.'
    return
  }

  submitting.value = true
  errorMessage.value = null
  try {
    await verifyTwoFactor(useRecovery.value
      ? { recovery_code: recoveryCode.value.trim() }
      : { code: code.value.join('') })
  }
  catch (error) {
    const info = parseApiError(error)
    // A 422 here also covers an expired sign-in (the password step was too long ago).
    errorMessage.value = info.status === 422
      ? 'That code did not work. Check the time on your phone, or start again from the sign-in page.'
      : info.message
    code.value = []
  }
  finally {
    submitting.value = false
  }
}

function toggleMode() {
  useRecovery.value = !useRecovery.value
  errorMessage.value = null
}
</script>

<template>
  <UCard>
    <template #header>
      <h1 class="text-xl font-semibold text-highlighted">
        Two-factor login
      </h1>
      <p class="mt-1 text-sm text-muted">
        {{ useRecovery
          ? 'Enter one of the recovery codes you saved when you turned on two-factor login.'
          : 'Open your authenticator app and enter the 6-digit code for CSMF-MRS.' }}
      </p>
    </template>

    <form
      class="flex flex-col gap-4"
      @submit.prevent="submit"
    >
      <UAlert
        v-if="errorMessage"
        color="error"
        variant="subtle"
        icon="i-lucide-circle-alert"
        :title="errorMessage"
        data-testid="challenge-error"
      />

      <UFormField
        v-if="!useRecovery"
        label="Authentication code"
      >
        <UPinInput
          id="challenge-code"
          v-model="code"
          :length="6"
          otp
          autofocus
          @complete="submit"
        />
      </UFormField>

      <UFormField
        v-else
        label="Recovery code"
      >
        <UInput
          id="challenge-recovery"
          v-model="recoveryCode"
          autocomplete="one-time-code"
          class="w-full"
          autofocus
        />
      </UFormField>

      <UButton
        type="submit"
        block
        :loading="submitting"
      >
        Verify
      </UButton>

      <div class="flex flex-wrap justify-between gap-2 text-sm">
        <UButton
          variant="link"
          color="neutral"
          class="px-0"
          @click="toggleMode"
        >
          {{ useRecovery ? 'Use an authenticator code' : 'Use a recovery code' }}
        </UButton>
        <UButton
          variant="link"
          color="neutral"
          class="px-0"
          to="/login"
        >
          Back to sign in
        </UButton>
      </div>
    </form>
  </UCard>
</template>
