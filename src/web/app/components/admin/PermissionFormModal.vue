<script setup lang="ts">
import { z } from 'zod'
import type { Form, FormSubmitEvent } from '@nuxt/ui'
import type { Permission } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

/** Same pattern the API enforces (StorePermissionRequest::TITLE_PATTERN). */
const TITLE_PATTERN = /^[a-z][a-z0-9-]{0,49}\.[a-z][a-z0-9_-]{0,49}$/

const props = defineProps<{ permission: Permission | null }>()
const open = defineModel<boolean>('open', { required: true })
const emit = defineEmits<{ saved: [] }>()

const client = useSanctumClient()
const toast = useToast()

const schema = z.object({
  title: z.string().trim().toLowerCase().regex(TITLE_PATTERN, 'Use the form resource.action in lower case, for example reports.view.'),
  description: z.string().max(255, 'Use 255 characters or fewer.'),
})
type Schema = z.output<typeof schema>

const form = useTemplateRef<Form<Schema>>('form')
const state = reactive<Schema>({ title: '', description: '' })
const submitting = ref(false)
const isProtected = computed(() => props.permission?.is_protected === true)

watch(open, (isOpen) => {
  if (isOpen) {
    state.title = props.permission?.title ?? ''
    state.description = props.permission?.description ?? ''
    form.value?.clear()
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  submitting.value = true
  try {
    const body = isProtected.value
      ? { description: event.data.description || null }
      : { title: event.data.title, description: event.data.description || null }
    if (props.permission) {
      await client(`/api/v1/admin/permissions/${props.permission.id}`, { method: 'put', body })
    }
    else {
      await client('/api/v1/admin/permissions', { method: 'post', body })
    }
    toast.add({ title: props.permission ? 'Changes saved' : `Permission ${event.data.title} added`, color: 'success', icon: 'i-lucide-circle-check' })
    open.value = false
    emit('saved')
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
  <UModal
    v-model:open="open"
    :title="permission ? 'Edit permission' : 'Add permission'"
    :description="permission ? undefined : 'A new permission has no effect until the system checks it. System Administrator receives it automatically.'"
    :dismissible="!submitting"
  >
    <template #body>
      <UForm
        id="permission-form"
        ref="form"
        :schema="schema"
        :state="state"
        class="flex flex-col gap-4"
        @submit="onSubmit"
      >
        <UFormField
          label="Title"
          name="title"
          required
          :description="isProtected ? 'Used by the system, so the title cannot change.' : 'resource.action, for example reports.print'"
        >
          <UInput
            id="permission-title"
            v-model="state.title"
            class="w-full font-mono"
            :disabled="isProtected"
            autocomplete="off"
          />
        </UFormField>
        <UFormField
          label="Description"
          name="description"
        >
          <UInput
            id="permission-description"
            v-model="state.description"
            class="w-full"
          />
        </UFormField>
      </UForm>
    </template>
    <template #footer>
      <div class="flex w-full justify-end gap-2">
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
          form="permission-form"
          :loading="submitting"
        >
          {{ permission ? 'Save changes' : 'Add permission' }}
        </UButton>
      </div>
    </template>
  </UModal>
</template>
