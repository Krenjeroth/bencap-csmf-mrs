<script setup lang="ts">
import { z } from 'zod'
import type { Form, FormSubmitEvent } from '@nuxt/ui'
import type { Office } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

/** Same pattern the API enforces (OfficeRequest). */
const SLUG_PATTERN = /^[a-z0-9]+(-[a-z0-9]+)*$/

/**
 * Create or edit an office. The web address is the guest form's /f/{slug};
 * left empty, the API makes it from the code.
 */
const props = defineProps<{ office: Office | null }>()
const open = defineModel<boolean>('open', { required: true })
const emit = defineEmits<{ saved: [] }>()

const client = useSanctumClient()
const toast = useToast()

const schema = z.object({
  code: z.string().trim().min(1, 'Enter the office code.').max(30, 'Use 30 characters or fewer.'),
  name: z.string().trim().min(1, 'Enter the office name.').max(150, 'Use 150 characters or fewer.'),
  slug: z.string().trim().toLowerCase().max(60, 'Use 60 characters or fewer.')
    .refine(value => value === '' || SLUG_PATTERN.test(value), 'Use lower-case letters, numbers and dashes only, for example og-library.'),
  sort_order: z.number().int('Use a whole number.').min(0).max(65535),
  is_active: z.boolean(),
})
type Schema = z.output<typeof schema>

const form = useTemplateRef<Form<Schema>>('form')
const state = reactive<Schema>({ code: '', name: '', slug: '', sort_order: 0, is_active: true })
const submitting = ref(false)
const isEdit = computed(() => props.office !== null)

watch(open, (isOpen) => {
  if (!isOpen) {
    return
  }
  state.code = props.office?.code ?? ''
  state.name = props.office?.name ?? ''
  state.slug = props.office?.slug ?? ''
  state.sort_order = props.office?.sort_order ?? 0
  state.is_active = props.office?.is_active ?? true
  form.value?.clear()
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  submitting.value = true
  try {
    if (props.office) {
      await client(`/api/v1/admin/offices/${props.office.id}`, { method: 'put', body: event.data })
      toast.add({ title: 'Changes saved', color: 'success', icon: 'i-lucide-circle-check' })
    }
    else {
      await client('/api/v1/admin/offices', { method: 'post', body: event.data })
      toast.add({ title: `Office ${event.data.code} added`, color: 'success', icon: 'i-lucide-circle-check' })
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
    :title="isEdit ? 'Edit office' : 'Add office'"
    :dismissible="!submitting"
  >
    <template #body>
      <UForm
        id="office-form"
        ref="form"
        :schema="schema"
        :state="state"
        class="flex flex-col gap-4"
        @submit="onSubmit"
      >
        <div class="grid gap-4 sm:grid-cols-3">
          <UFormField
            label="Code"
            name="code"
            required
            class="sm:col-span-1"
          >
            <UInput
              id="office-code"
              v-model="state.code"
              class="w-full"
              autocomplete="off"
            />
          </UFormField>
          <UFormField
            label="Order"
            name="sort_order"
            help="Lower comes first."
            class="sm:col-span-2"
          >
            <UInputNumber
              id="office-sort-order"
              v-model="state.sort_order"
              :min="0"
              :max="65535"
              class="w-full"
            />
          </UFormField>
        </div>

        <UFormField
          label="Office name"
          name="name"
          required
        >
          <UInput
            id="office-name"
            v-model="state.name"
            class="w-full"
            autocomplete="off"
          />
        </UFormField>

        <UFormField
          label="Guest form address"
          name="slug"
          :description="isEdit ? 'Changing this breaks printed QR codes that point to the old address.' : 'Left empty, it is made from the code.'"
        >
          <UInput
            id="office-slug"
            v-model="state.slug"
            class="w-full"
            autocomplete="off"
            :ui="{ base: 'pl-9' }"
          >
            <template #leading>
              <span class="text-sm text-muted">/f/</span>
            </template>
          </UInput>
        </UFormField>

        <UFormField name="is_active">
          <USwitch
            id="office-active"
            v-model="state.is_active"
            label="Office is active"
            description="Turn this off, rather than deleting, for an office that is no longer in the charter."
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
          form="office-form"
          :loading="submitting"
        >
          {{ isEdit ? 'Save changes' : 'Add office' }}
        </UButton>
      </div>
    </template>
  </UModal>
</template>
