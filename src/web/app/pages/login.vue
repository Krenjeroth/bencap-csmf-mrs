<script setup lang="ts">
import { z } from 'zod'
import type { FormSubmitEvent } from '@nuxt/ui'
import { parseApiError } from '~/utils/apiError'

definePageMeta({ layout: 'auth', middleware: ['sanctum:guest'] })
useHead({ title: 'Sign in · CSMF-MRS' })

const schema = z.object({
  email: z.email('Enter a valid email address.'),
  password: z.string().min(1, 'Enter your password.'),
  remember: z.boolean(),
})
type Schema = z.output<typeof schema>

const state = reactive<Schema>({ email: '', password: '', remember: false })
const submitting = ref(false)
const errorMessage = ref<string | null>(null)
const { signIn } = useSignIn()

async function onSubmit(event: FormSubmitEvent<Schema>) {
  submitting.value = true
  errorMessage.value = null
  try {
    await signIn(event.data.email, event.data.password, event.data.remember)
  }
  catch (error) {
    errorMessage.value = parseApiError(error).message
    state.password = ''
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <UCard>
      <template #header>
        <h1 class="text-xl font-semibold text-highlighted">
          Sign in
        </h1>
        <p class="mt-1 text-sm text-muted">
          For office staff and system administrators. Clients giving feedback do not need an account.
        </p>
      </template>

      <UForm
        :schema="schema"
        :state="state"
        class="flex flex-col gap-4"
        @submit="onSubmit"
      >
        <UAlert
          v-if="errorMessage"
          color="error"
          variant="subtle"
          icon="i-lucide-circle-alert"
          :title="errorMessage"
          data-testid="login-error"
        />

        <UFormField
          label="Email"
          name="email"
        >
          <UInput
            id="login-email"
            v-model="state.email"
            type="email"
            autocomplete="username"
            class="w-full"
            autofocus
          />
        </UFormField>

        <UFormField
          label="Password"
          name="password"
        >
          <UInput
            id="login-password"
            v-model="state.password"
            type="password"
            autocomplete="current-password"
            class="w-full"
          />
        </UFormField>

        <UCheckbox
          id="login-remember"
          v-model="state.remember"
          label="Keep me signed in on this computer"
        />

        <UButton
          type="submit"
          block
          :loading="submitting"
        >
          Sign in
        </UButton>

        <p class="text-xs text-muted">
          Forgot your password? Ask your System Administrator to reset it.
        </p>
      </UForm>
    </UCard>

    <AppApiStatusCard />
  </div>
</template>
