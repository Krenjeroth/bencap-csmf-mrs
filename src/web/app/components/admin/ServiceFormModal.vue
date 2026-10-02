<script setup lang="ts">
import { z } from 'zod'
import type { Form, FormSubmitEvent } from '@nuxt/ui'
import type { OfficeOption, Service } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

/**
 * Create or edit one Citizen's Charter service. A user limited to one office
 * can only pick that office (the API enforces it too). New services go to
 * the end of their office's list.
 */
const props = defineProps<{
  service: Service | null
  officeOptions: OfficeOption[]
  serviceTypeOptions: { id: number, type: string }[]
  /** Office the signed-in user is limited to, or null. */
  lockedOfficeId: number | null
  /** Office picked in the list filter, used as the default for a new service. */
  defaultOfficeId?: number
}>()
const open = defineModel<boolean>('open', { required: true })
const emit = defineEmits<{ saved: [] }>()

const client = useSanctumClient()
const toast = useToast()

const schema = z.object({
  office_id: z.number({ error: 'Choose the office.' }),
  service_type_id: z.number({ error: 'Choose the service type.' }),
  name: z.string().trim().min(1, 'Enter the service name.').max(255, 'Use 255 characters or fewer.'),
  charter_year: z.number().int('Use a whole number.').min(2000, 'Use a year from 2000 to 2100.').max(2100, 'Use a year from 2000 to 2100.'),
  sort_order: z.number().int('Use a whole number.').min(0).max(65535).optional(),
  is_active: z.boolean(),
})
type Schema = z.output<typeof schema>

const form = useTemplateRef<Form<Schema>>('form')
const state = reactive<Partial<Schema>>({})
const submitting = ref(false)
const isEdit = computed(() => props.service !== null)

const officeItems = computed(() => props.officeOptions
  .filter(office => office.is_active || office.id === props.service?.office?.id)
  .map(office => ({ label: `${office.code} – ${office.name}`, value: office.id })))
const typeItems = computed(() => props.serviceTypeOptions.map(type => ({ label: type.type, value: type.id })))

watch(open, (isOpen) => {
  if (!isOpen) {
    return
  }
  state.office_id = props.service?.office?.id ?? props.lockedOfficeId ?? props.defaultOfficeId
  state.service_type_id = props.service?.service_type?.id
  state.name = props.service?.name ?? ''
  state.charter_year = props.service?.charter_year ?? new Date().getFullYear()
  state.sort_order = props.service?.sort_order
  state.is_active = props.service?.is_active ?? true
  form.value?.clear()
}, { immediate: true })

async function onSubmit(event: FormSubmitEvent<Schema>) {
  submitting.value = true
  try {
    if (props.service) {
      await client(`/api/v1/admin/services/${props.service.id}`, { method: 'put', body: event.data })
      toast.add({ title: 'Changes saved', color: 'success', icon: 'i-lucide-circle-check' })
    }
    else {
      const { sort_order: _sortOrder, ...body } = event.data
      await client('/api/v1/admin/services', { method: 'post', body })
      toast.add({ title: 'Service added', color: 'success', icon: 'i-lucide-circle-check' })
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
    :title="isEdit ? 'Edit service' : 'Add service'"
    :ui="{ content: 'sm:max-w-xl' }"
    :dismissible="!submitting"
  >
    <template #body>
      <UForm
        id="service-form"
        ref="form"
        :schema="schema"
        :state="state"
        class="flex flex-col gap-4"
        @submit="onSubmit"
      >
        <UFormField
          label="Office"
          name="office_id"
          required
          :description="lockedOfficeId !== null ? 'You can only manage services of your own office.' : undefined"
        >
          <USelectMenu
            id="service-office"
            v-model="state.office_id"
            :items="officeItems"
            value-key="value"
            placeholder="Choose an office"
            :disabled="lockedOfficeId !== null"
            class="w-full"
          />
        </UFormField>

        <UFormField
          label="Service name"
          name="name"
          required
          description="As written in the Citizen's Charter. Sub-services are written “Parent – Service”."
        >
          <UTextarea
            id="service-name"
            v-model="state.name"
            :rows="2"
            autoresize
            class="w-full"
          />
        </UFormField>

        <div class="grid gap-4 sm:grid-cols-2">
          <UFormField
            label="Service type"
            name="service_type_id"
            required
          >
            <USelect
              id="service-type"
              v-model="state.service_type_id"
              :items="typeItems"
              placeholder="Choose a type"
              class="w-full"
            />
          </UFormField>
          <UFormField
            label="Charter year"
            name="charter_year"
            required
          >
            <UInputNumber
              id="service-charter-year"
              v-model="state.charter_year"
              :min="2000"
              :max="2100"
              :format-options="{ useGrouping: false }"
              class="w-full"
            />
          </UFormField>
        </div>

        <UFormField
          v-if="isEdit"
          label="Order within the office"
          name="sort_order"
          description="Lower comes first."
        >
          <UInputNumber
            id="service-sort-order"
            v-model="state.sort_order"
            :min="0"
            :max="65535"
            class="w-full"
          />
        </UFormField>

        <UFormField name="is_active">
          <USwitch
            id="service-active"
            v-model="state.is_active"
            label="Service is active"
            description="Turn this off, rather than deleting, for a service dropped from the charter."
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
          form="service-form"
          :loading="submitting"
        >
          {{ isEdit ? 'Save changes' : 'Add service' }}
        </UButton>
      </div>
    </template>
  </UModal>
</template>
