<script setup lang="ts">
import { z } from 'zod'
import type { Form, FormSubmitEvent } from '@nuxt/ui'
import type { CreatedUser, Resource, RoleSummary, User } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

/**
 * Create or edit a staff account. Creating returns a one-time password,
 * shown once inside this modal. Role changes go to PUT /users/{id}/roles.
 */
const props = defineProps<{
  user: User | null
  roleOptions: RoleSummary[]
}>()
const open = defineModel<boolean>('open', { required: true })
const emit = defineEmits<{ saved: [user: User] }>()

const client = useSanctumClient()
const { user: me } = useCurrentUser()
const toast = useToast()

const schema = z.object({
  name: z.string().trim().min(1, 'Enter the full name.').max(150, 'Use 150 characters or fewer.'),
  email: z.email('Enter a valid email address.').max(255),
  is_active: z.boolean(),
  role_ids: z.array(z.number()),
})
type Schema = z.output<typeof schema>

const form = useTemplateRef<Form<Schema>>('form')
const state = reactive<Schema>({ name: '', email: '', is_active: true, role_ids: [] })
const submitting = ref(false)
const created = ref<{ email: string, password: string } | null>(null)

const isEdit = computed(() => props.user !== null)
const isSelf = computed(() => props.user?.id === me.value?.id)

// Only a System Administrator may grant or remove that role (the API enforces it too).
const roleItems = computed(() => props.roleOptions.map(role => ({
  label: role.is_system ? `${role.title} (all permissions)` : role.title,
  id: role.id,
  disabled: isSelf.value || (role.is_system && !me.value?.is_system_administrator),
})))

watch(open, (isOpen) => {
  if (!isOpen) {
    return
  }
  created.value = null
  state.name = props.user?.name ?? ''
  state.email = props.user?.email ?? ''
  state.is_active = props.user?.is_active ?? true
  state.role_ids = props.user?.roles?.map(role => role.id) ?? []
  form.value?.clear()
}, { immediate: true })

function sameRoles(a: number[], b: number[]) {
  return a.length === b.length && [...a].sort().every((id, i) => id === [...b].sort()[i])
}

async function onSubmit(event: FormSubmitEvent<Schema>) {
  submitting.value = true
  try {
    let saved: User
    if (!props.user) {
      const response = await client<CreatedUser>('/api/v1/admin/users', { method: 'post', body: event.data })
      saved = response.data
      created.value = { email: saved.email, password: response.temporary_password }
    }
    else {
      const { role_ids, ...details } = event.data
      saved = (await client<Resource<User>>(`/api/v1/admin/users/${props.user.id}`, { method: 'put', body: details })).data
      const before = props.user.roles?.map(role => role.id) ?? []
      if (!isSelf.value && !sameRoles(before, role_ids)) {
        saved = (await client<Resource<User>>(`/api/v1/admin/users/${props.user.id}/roles`, { method: 'put', body: { role_ids } })).data
      }
      toast.add({ title: 'Changes saved', color: 'success', icon: 'i-lucide-circle-check' })
      open.value = false
    }
    emit('saved', saved)
  }
  catch (error) {
    const info = parseApiError(error)
    const fieldErrors = Object.entries(info.fields).map(([name, message]) => ({ name: name.split('.')[0] ?? name, message }))
    if (fieldErrors.length) {
      form.value?.setErrors(fieldErrors)
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
  <UModal
    v-model:open="open"
    :title="isEdit ? 'Edit user' : 'Add user'"
    :description="isEdit ? user?.email : 'The system creates a temporary password for the new account.'"
    :dismissible="!submitting"
  >
    <template #body>
      <AdminTemporaryPassword
        v-if="created"
        :password="created.password"
        :email="created.email"
      />

      <UForm
        v-else
        id="user-form"
        ref="form"
        :schema="schema"
        :state="state"
        class="flex flex-col gap-4"
        @submit="onSubmit"
      >
        <UFormField
          label="Full name"
          name="name"
          required
        >
          <UInput
            id="user-name"
            v-model="state.name"
            class="w-full"
            autocomplete="off"
          />
        </UFormField>

        <UFormField
          label="Email"
          name="email"
          required
          description="Used to sign in."
        >
          <UInput
            id="user-email"
            v-model="state.email"
            type="email"
            class="w-full"
            autocomplete="off"
          />
        </UFormField>

        <UFormField
          name="role_ids"
          label="Roles"
          :description="isSelf ? 'You cannot change your own roles.' : undefined"
        >
          <UCheckboxGroup
            id="user-roles"
            v-model="state.role_ids"
            :items="roleItems"
            value-key="id"
          />
        </UFormField>

        <UFormField name="is_active">
          <USwitch
            id="user-active"
            v-model="state.is_active"
            label="Account is active"
            :description="isSelf ? 'You cannot deactivate your own account.' : 'Inactive accounts cannot sign in.'"
            :disabled="isSelf"
          />
        </UFormField>
      </UForm>
    </template>

    <template #footer>
      <div class="flex w-full justify-end gap-2">
        <template v-if="created">
          <UButton @click="open = false">
            Done
          </UButton>
        </template>
        <template v-else>
          <UButton
            color="neutral"
            variant="outline"
            :disabled="submitting"
            @click="open = false"
          >
            Cancel
          </UButton>
          <UButton
            type="submit"
            form="user-form"
            :loading="submitting"
          >
            {{ isEdit ? 'Save changes' : 'Add user' }}
          </UButton>
        </template>
      </div>
    </template>
  </UModal>
</template>
