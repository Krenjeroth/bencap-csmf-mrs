<script setup lang="ts">
import type { Me } from '~/types/api'
import { parseApiError } from '~/utils/apiError'
import { pendingAccountStep } from '~/utils/accountSteps'

definePageMeta({ middleware: ['sanctum:auth'] })
useHead({ title: 'Two-factor login · CSMF-MRS' })

const client = useSanctumClient()
const { refreshIdentity } = useSanctumAuth()
const { user } = useCurrentUser()
const { withConfirmation } = usePasswordConfirmation()
const toast = useToast()

/** idle: off · setup: secret issued, waiting for first code · on: confirmed */
const stage = ref<'loading' | 'idle' | 'setup' | 'on'>('loading')
const qrDataUrl = ref<string | null>(null)
const secretKey = ref<string | null>(null)
const code = ref<string[]>([])
const recoveryCodes = ref<string[]>([])
const busy = ref(false)
const codeError = ref<string | null>(null)

function notifyError(error: unknown) {
  // Cancelling the password prompt is not an error worth reporting.
  if (error instanceof Error && error.message === 'Password confirmation cancelled') {
    return
  }
  toast.add({ title: parseApiError(error).message, color: 'error', icon: 'i-lucide-circle-alert' })
}

async function loadSetupDetails() {
  const qr = await client<{ svg?: string }>('/api/user/two-factor-qr-code')
  if (!qr?.svg) {
    return false
  }
  // Rendered as an image, never injected as markup.
  qrDataUrl.value = `data:image/svg+xml;base64,${btoa(qr.svg)}`
  const key = await client<{ secretKey?: string }>('/api/user/two-factor-secret-key')
  secretKey.value = key?.secretKey ?? null
  return true
}

// Every Fortify two-factor route asks for the password again, so nothing
// is fetched until the user acts. "Turn on" also resumes an unfinished setup.
onMounted(() => {
  stage.value = user.value?.two_factor_enabled ? 'on' : 'idle'
})

async function enable() {
  busy.value = true
  try {
    await withConfirmation(() => client('/api/user/two-factor-authentication', { method: 'post' }))
    await withConfirmation(loadSetupDetails)
    stage.value = 'setup'
  }
  catch (error) {
    notifyError(error)
  }
  finally {
    busy.value = false
  }
}

async function confirmSetup() {
  const value = code.value.join('')
  if (value.length !== 6) {
    codeError.value = 'Enter the 6-digit code from your authenticator app.'
    return
  }
  busy.value = true
  codeError.value = null
  try {
    await withConfirmation(() => client('/api/user/confirmed-two-factor-authentication', { method: 'post', body: { code: value } }))
    recoveryCodes.value = await withConfirmation(() => client<string[]>('/api/user/two-factor-recovery-codes'))
    await refreshIdentity()
    stage.value = 'on'
    qrDataUrl.value = null
    secretKey.value = null
    toast.add({ title: 'Two-factor login is on', color: 'success', icon: 'i-lucide-shield-check' })
  }
  catch (error) {
    const info = parseApiError(error)
    if (info.status === 422) {
      codeError.value = 'That code did not match. Check the time on your phone and try the newest code.'
      code.value = []
    }
    else {
      notifyError(error)
    }
  }
  finally {
    busy.value = false
  }
}

async function showRecoveryCodes(regenerate: boolean) {
  busy.value = true
  try {
    if (regenerate) {
      await withConfirmation(() => client('/api/user/two-factor-recovery-codes', { method: 'post' }))
    }
    recoveryCodes.value = await withConfirmation(() => client<string[]>('/api/user/two-factor-recovery-codes'))
  }
  catch (error) {
    notifyError(error)
  }
  finally {
    busy.value = false
  }
}

async function disable() {
  busy.value = true
  try {
    await withConfirmation(() => client('/api/user/two-factor-authentication', { method: 'delete' }))
    await refreshIdentity()
    recoveryCodes.value = []
    stage.value = 'idle'
    toast.add({ title: 'Two-factor login is off', color: 'neutral', icon: 'i-lucide-shield-off' })
  }
  catch (error) {
    notifyError(error)
  }
  finally {
    busy.value = false
  }
}

async function copyCodes() {
  try {
    await navigator.clipboard.writeText(recoveryCodes.value.join('\n'))
    toast.add({ title: 'Recovery codes copied', color: 'success', icon: 'i-lucide-copy-check' })
  }
  catch {
    toast.add({ title: 'Copy failed. Select the codes and copy them by hand.', color: 'warning' })
  }
}

const nextStep = computed(() => pendingAccountStep(useSanctumUser<Me>().value))
</script>

<template>
  <UDashboardPanel id="account-security">
    <template #header>
      <UDashboardNavbar title="Two-factor login">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex max-w-2xl flex-col gap-6">
        <UAlert
          v-if="user?.two_factor_required && stage !== 'on'"
          color="warning"
          variant="subtle"
          icon="i-lucide-shield-alert"
          title="Required for System Administrators"
          description="Turn on two-factor login to continue. You will need a phone with an authenticator app such as Google Authenticator or Microsoft Authenticator."
        />

        <p class="text-muted">
          Two-factor login asks for a 6-digit code from your phone after your password, so a stolen password alone cannot open your account.
        </p>

        <USkeleton
          v-if="stage === 'loading'"
          class="h-24 w-full"
        />

        <UCard v-else-if="stage === 'idle'">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <UBadge
                color="neutral"
                variant="subtle"
                icon="i-lucide-shield-off"
              >
                Off
              </UBadge>
              <span class="text-sm text-muted">Only your password protects this account.</span>
            </div>
            <UButton
              icon="i-lucide-shield-check"
              :loading="busy"
              @click="enable"
            >
              Turn on
            </UButton>
          </div>
        </UCard>

        <UCard v-else-if="stage === 'setup'">
          <template #header>
            <h2 class="font-semibold text-highlighted">
              Set up your authenticator app
            </h2>
          </template>
          <div class="grid gap-6 sm:grid-cols-[auto_1fr]">
            <img
              v-if="qrDataUrl"
              :src="qrDataUrl"
              alt="QR code to add CSMF-MRS to your authenticator app"
              class="size-48 rounded-md bg-white p-2"
            >
            <ol class="flex list-decimal flex-col gap-3 pl-5 text-sm">
              <li>In your authenticator app, add an account and scan this QR code.</li>
              <li v-if="secretKey">
                Can't scan? Enter this key instead:
                <code class="mt-1 block break-all rounded bg-elevated px-2 py-1 font-mono text-highlighted">{{ secretKey }}</code>
              </li>
              <li>Enter the 6-digit code the app shows.</li>
            </ol>
          </div>
          <form
            class="mt-6 flex flex-col gap-3"
            @submit.prevent="confirmSetup"
          >
            <UFormField
              label="Code from your app"
              :error="codeError ?? undefined"
            >
              <UPinInput
                id="setup-code"
                v-model="code"
                :length="6"
                otp
                @complete="confirmSetup"
              />
            </UFormField>
            <div>
              <UButton
                type="submit"
                :loading="busy"
              >
                Confirm and turn on
              </UButton>
            </div>
          </form>
        </UCard>

        <UCard v-else>
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <UBadge
                color="success"
                variant="subtle"
                icon="i-lucide-shield-check"
              >
                On
              </UBadge>
              <span class="text-sm text-muted">You will be asked for a code each time you sign in.</span>
            </div>
            <div class="flex flex-wrap gap-2">
              <UButton
                color="neutral"
                variant="outline"
                icon="i-lucide-list"
                :loading="busy"
                @click="showRecoveryCodes(false)"
              >
                Show recovery codes
              </UButton>
              <UButton
                v-if="!user?.two_factor_required"
                color="error"
                variant="outline"
                icon="i-lucide-shield-off"
                :loading="busy"
                @click="disable"
              >
                Turn off
              </UButton>
            </div>
          </div>
        </UCard>

        <UCard v-if="recoveryCodes.length">
          <template #header>
            <h2 class="font-semibold text-highlighted">
              Recovery codes
            </h2>
            <p class="mt-1 text-sm text-muted">
              Each code works once if you lose your phone. Store them somewhere safe and offline.
            </p>
          </template>
          <ul
            class="grid grid-cols-2 gap-2 font-mono text-sm text-highlighted"
            data-testid="recovery-codes"
          >
            <li
              v-for="item in recoveryCodes"
              :key="item"
            >
              {{ item }}
            </li>
          </ul>
          <template #footer>
            <div class="flex flex-wrap gap-2">
              <UButton
                color="neutral"
                variant="outline"
                icon="i-lucide-copy"
                @click="copyCodes"
              >
                Copy
              </UButton>
              <UButton
                color="neutral"
                variant="ghost"
                icon="i-lucide-refresh-cw"
                :loading="busy"
                @click="showRecoveryCodes(true)"
              >
                Make new codes
              </UButton>
            </div>
          </template>
        </UCard>

        <div v-if="stage === 'on' && !nextStep && user?.two_factor_required">
          <UButton
            to="/"
            trailing-icon="i-lucide-arrow-right"
          >
            Continue to CSMF-MRS
          </UButton>
        </div>
      </div>

      <AccountPasswordConfirmModal />
    </template>
  </UDashboardPanel>
</template>
