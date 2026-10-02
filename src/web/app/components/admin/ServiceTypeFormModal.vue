<script setup lang="ts">
import { z } from 'zod'
import type { Form, FormSubmitEvent } from '@nuxt/ui'
import type { ServiceType } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

/** Create or edit a service type (for example Internal or External). */
const props = defineProps<{ serviceType: ServiceType | null }>()
const open = defineModel<boolean>('open', { required: true })
const emit = defineEmits<{ saved: [] }>()

const client = useSanctumClient()
const toast = useToast()

const schema = z.object({
  type: z.string().trim().min(1, 'Enter the type name.').max(50, 'Use 50 characters or fewer.'),
  description: z.string().max(255, 'Use 255 characters or fewer.'),
})
type Schema = z.output<typeof schema>

const form = useTemplateRef<Form<Schema>>('form')
const state = reactive<Schema>({ type: '', description: '' })
const submitting = ref(false)
const isEdit = computed(() => props.serviceType !== null)

watch(open, (isOpen) => {
  if (isOpen) {
    state.type = props.serviceType?.type ?? ''
    state.description = props.serviceType?.description ?? ''
    form.value?.clear()
  }
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  submitting.value = true
  try {
    const body = { type: event.data.type, description: event.data.description || null }
    if (props.serviceType) {
      await client(`/api/v1/admin/service-types/${props.serviceType.id}`, { method: 'put', body })
      toast.add({ title: 'Changes saved', color: 'success', icon: 'i-lucide-circle-check' })
    }
    else {
      await client('/api/v1/admin/service-types', { method: 'post', body })
      toast.add({ title: `Service type ${event.data.type} added`, color: 'success', icon: 'i-lucide-circle-check' })
    }
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
    :title="isEdit ? 'Edit service type' : 'Add service type'"
    :dismissible="!submitting"
  >
    <template #body>
      <UForm
        id="service-type-form"
        ref="form"
        :schema="schema"
        :state="state"
        class="flex flex-col gap-4"
        @submit="onSubmit"
      >
        <UFormField
          label="Type"
          name="type"
          required
        >
          <UInput
            id="service-type-type"
            v-model="state.type"
            class="w-full"
            autocomplete="off"
          />
        </UFormField>

        <UFormField
          label="Description"
          name="description"
        >
          <UTextarea
            id="service-type-description"
            v-model="state.description"
            :rows="2"
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
          form="service-type-form"
          :loading="submitting"
        >
          {{ isEdit ? 'Save changes' : 'Add service type' }}
        </UButton>
      </div>
    </template>
  </UModal>
</template>
