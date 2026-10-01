<script setup lang="ts">
import { z } from 'zod'
import type { Form, FormSubmitEvent } from '@nuxt/ui'
import type { Me } from '~/types/api'
import { parseApiError } from '~/utils/apiError'
import { pendingAccountStep } from '~/utils/accountSteps'
import { PASSWORD_RULES, passwordSchema } from '~/utils/passwordPolicy'

definePageMeta({ middleware: ['sanctum:auth'] })
useHead({ title: 'Change password · CSMF-MRS' })

const client = useSanctumClient()
const { refreshIdentity } = useSanctumAuth()
const { user } = useCurrentUser()
const toast = useToast()

const schema = z.object({
  current_password: z.string().min(1, 'Enter your current password.'),
  password: passwordSchema,
  password_confirmation: z.string(),
}).refine(data => data.password === data.password_confirmation, {
  message: 'The two new passwords do not match.',
  path: ['password_confirmation'],
}).refine(data => data.password !== data.current_password, {
  message: 'Choose a password different from your current one.',
  path: ['password'],
})
type Schema = z.output<typeof schema>

const form = useTemplateRef<Form<Schema>>('form')
const state = reactive<Schema>({ current_password: '', password: '', password_confirmation: '' })
const submitting = ref(false)

async function onSubmit(event: FormSubmitEvent<Schema>) {
  submitting.value = true
  try {
    await client('/api/user/password', { method: 'put', body: event.data })
    state.current_password = ''
    state.password = ''
    state.password_confirmation = ''
    await refreshIdentity()
    toast.add({ title: 'Password changed', color: 'success', icon: 'i-lucide-circle-check' })
    await navigateTo(pendingAccountStep(useSanctumUser<Me>().value) ?? '/', { replace: true })
  }
  catch (error) {
    const info = parseApiError(error)
    if (Object.keys(info.fields).length) {
      form.value?.setErrors(Object.entries(info.fields).map(([name, message]) => ({ name, message })))
    }
    else {
      toast.add({ title: info.message, color: 'error', icon: 'i-lucide-circle-alert' })
    }
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <UDashboardPanel id="account-password">
    <template #header>
      <UDashboardNavbar title="Change password">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex max-w-lg flex-col gap-6">
        <UAlert
          v-if="user?.must_change_password"
          color="warning"
          variant="subtle"
          icon="i-lucide-key-round"
          title="Choose your own password"
          description="You signed in with a temporary password. Replace it to continue."
        />

        <UForm
          ref="form"
          :schema="schema"
          :state="state"
          class="flex flex-col gap-4"
          @submit="onSubmit"
        >
          <UFormField
            label="Current password"
            name="current_password"
            :description="user?.must_change_password ? 'The temporary password you were given.' : undefined"
          >
            <UInput
              id="current-password"
              v-model="state.current_password"
              type="password"
              autocomplete="current-password"
              class="w-full"
            />
          </UFormField>

          <UFormField
            label="New password"
            name="password"
          >
            <UInput
              id="new-password"
              v-model="state.password"
              type="password"
              autocomplete="new-password"
              class="w-full"
            />
          </UFormField>

          <ul
            class="grid gap-1 text-sm"
            aria-label="Password requirements"
          >
            <li
              v-for="rule in PASSWORD_RULES"
              :key="rule.label"
              class="flex items-center gap-2"
              :class="rule.test(state.password) ? 'text-success' : 'text-muted'"
            >
              <UIcon
                :name="rule.test(state.password) ? 'i-lucide-circle-check' : 'i-lucide-circle'"
                class="size-4 shrink-0"
              />
              {{ rule.label }}
            </li>
          </ul>

          <UFormField
            label="Confirm new password"
            name="password_confirmation"
          >
            <UInput
              id="confirm-password"
              v-model="state.password_confirmation"
              type="password"
              autocomplete="new-password"
              class="w-full"
            />
          </UFormField>

          <div>
            <UButton
              type="submit"
              :loading="submitting"
            >
              Change password
            </UButton>
          </div>
        </UForm>
      </div>
    </template>
  </UDashboardPanel>
</template>
