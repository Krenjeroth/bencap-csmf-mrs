<script setup lang="ts">
import { z } from 'zod'
import type { Form, FormSubmitEvent } from '@nuxt/ui'
import type { PermissionOption, Resource, Role } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

/**
 * Create or edit a role and choose its permissions, grouped by resource.
 * The System Administrator role always has every permission, so its
 * checklist is read-only and its title is fixed.
 */
const props = defineProps<{
  role: Role | null
  permissionOptions: PermissionOption[]
}>()
const open = defineModel<boolean>('open', { required: true })
const emit = defineEmits<{ saved: [] }>()

const client = useSanctumClient()
const { can } = useCurrentUser()
const toast = useToast()

const schema = z.object({
  title: z.string().trim().min(1, 'Enter a role name.').max(100, 'Use 100 characters or fewer.'),
  description: z.string().max(255, 'Use 255 characters or fewer.'),
  permission_ids: z.array(z.number()),
})
type Schema = z.output<typeof schema>

const form = useTemplateRef<Form<Schema>>('form')
const state = reactive<Schema>({ title: '', description: '', permission_ids: [] })
const submitting = ref(false)
const loadingRole = ref(false)

const isEdit = computed(() => props.role !== null)
const isSystem = computed(() => props.role?.is_system === true)
const canEditPermissions = computed(() => !isSystem.value && (isEdit.value ? can('roles.update') : can('roles.create')))

const groups = computed(() => {
  const byResource = new Map<string, PermissionOption[]>()
  for (const permission of props.permissionOptions) {
    const list = byResource.get(permission.resource) ?? []
    list.push(permission)
    byResource.set(permission.resource, list)
  }
  return [...byResource.entries()].map(([resource, permissions]) => ({ resource, permissions }))
})

function groupState(permissions: PermissionOption[]): boolean | 'indeterminate' {
  const selected = permissions.filter(p => state.permission_ids.includes(p.id)).length
  return selected === 0 ? false : selected === permissions.length ? true : 'indeterminate'
}

function toggleGroup(permissions: PermissionOption[], value: boolean | 'indeterminate') {
  const ids = permissions.map(p => p.id)
  state.permission_ids = value === true
    ? [...new Set([...state.permission_ids, ...ids])]
    : state.permission_ids.filter(id => !ids.includes(id))
}

function togglePermission(id: number, value: boolean | 'indeterminate') {
  state.permission_ids = value === true
    ? [...new Set([...state.permission_ids, id])]
    : state.permission_ids.filter(existing => existing !== id)
}

watch(open, async (isOpen) => {
  if (!isOpen) {
    return
  }
  state.title = props.role?.title ?? ''
  state.description = props.role?.description ?? ''
  state.permission_ids = []
  form.value?.clear()

  if (props.role) {
    loadingRole.value = true
    try {
      const full = (await client<Resource<Role>>(`/api/v1/admin/roles/${props.role.id}`)).data
      state.permission_ids = full.permissions?.map(p => p.id) ?? []
    }
    catch (error) {
      toast.add({ title: parseApiError(error).message, color: 'error' })
    }
    finally {
      loadingRole.value = false
    }
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  submitting.value = true
  try {
    const body = { title: event.data.title, description: event.data.description || null }
    if (!props.role) {
      await client('/api/v1/admin/roles', { method: 'post', body: { ...body, permission_ids: event.data.permission_ids } })
      toast.add({ title: `Role "${event.data.title}" added`, color: 'success', icon: 'i-lucide-circle-check' })
    }
    else {
      await client(`/api/v1/admin/roles/${props.role.id}`, { method: 'put', body })
      if (canEditPermissions.value) {
        await client(`/api/v1/admin/roles/${props.role.id}/permissions`, { method: 'put', body: { permission_ids: event.data.permission_ids } })
      }
      toast.add({ title: 'Changes saved', color: 'success', icon: 'i-lucide-circle-check' })
    }
    open.value = false
    emit('saved')
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
    :title="isEdit ? 'Edit role' : 'Add role'"
    :ui="{ content: 'sm:max-w-2xl' }"
    :dismissible="!submitting"
  >
    <template #body>
      <UForm
        id="role-form"
        ref="form"
        :schema="schema"
        :state="state"
        class="flex flex-col gap-4"
        @submit="onSubmit"
      >
        <UFormField
          label="Role name"
          name="title"
          required
          :description="isSystem ? 'The System Administrator role cannot be renamed.' : undefined"
        >
          <UInput
            id="role-title"
            v-model="state.title"
            class="w-full"
            :disabled="isSystem"
          />
        </UFormField>

        <UFormField
          label="Description"
          name="description"
        >
          <UTextarea
            id="role-description"
            v-model="state.description"
            :rows="2"
            class="w-full"
          />
        </UFormField>

        <UFormField
          name="permission_ids"
          label="Permissions"
          :description="isSystem ? 'System Administrator always has every permission.' : 'Choose what people with this role can see and do.'"
        >
          <USkeleton
            v-if="loadingRole"
            class="h-40 w-full"
          />
          <div
            v-else-if="!isSystem"
            class="grid gap-3 sm:grid-cols-2"
          >
            <fieldset
              v-for="group in groups"
              :key="group.resource"
              class="rounded-md border border-default p-3"
            >
              <legend class="px-1">
                <UCheckbox
                  :id="`group-${group.resource}`"
                  :model-value="groupState(group.permissions)"
                  :label="group.resource"
                  :disabled="!canEditPermissions"
                  :ui="{ label: 'font-semibold capitalize' }"
                  @update:model-value="toggleGroup(group.permissions, $event)"
                />
              </legend>
              <div class="mt-1 flex flex-col gap-2">
                <UCheckbox
                  v-for="permission in group.permissions"
                  :id="`permission-${permission.id}`"
                  :key="permission.id"
                  :model-value="state.permission_ids.includes(permission.id)"
                  :label="permission.title"
                  :description="permission.description ?? undefined"
                  :disabled="!canEditPermissions"
                  :ui="{ label: 'font-mono text-xs' }"
                  @update:model-value="togglePermission(permission.id, $event)"
                />
              </div>
            </fieldset>
          </div>
        </UFormField>
      </UForm>
    </template>

    <template #footer>
      <div class="flex w-full items-center justify-between gap-2">
        <span class="text-sm text-muted tabular-nums">
          {{ isSystem ? `${permissionOptions.length} of ${permissionOptions.length}` : `${state.permission_ids.length} of ${permissionOptions.length}` }} permissions
        </span>
        <div class="flex gap-2">
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
            form="role-form"
            :loading="submitting"
          >
            {{ isEdit ? 'Save changes' : 'Add role' }}
          </UButton>
        </div>
      </div>
    </template>
  </UModal>
</template>
