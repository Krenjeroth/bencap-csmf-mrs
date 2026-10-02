<script setup lang="ts">
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui'
import type { ServiceType } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

definePageMeta({ middleware: ['sanctum:auth', 'permission'], permission: 'service-types.view' })
useHead({ title: 'Service types · CSMF-MRS' })

const client = useSanctumClient()
const { can } = useCurrentUser()
const toast = useToast()

// A handful of rows (Internal, External), so the API returns them unpaged.
const rows = ref<ServiceType[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
async function load() {
  loading.value = true
  error.value = null
  try {
    rows.value = (await client<{ data: ServiceType[] }>('/api/v1/admin/service-types')).data
  }
  catch (e) {
    error.value = parseApiError(e).message
  }
  finally {
    loading.value = false
  }
}
onMounted(load)

const columns: TableColumn<ServiceType>[] = [
  { accessorKey: 'type', header: 'Type' },
  { accessorKey: 'description', header: 'Description' },
  { accessorKey: 'services_count', header: 'Services', meta: { class: { th: 'text-right', td: 'text-right' } } },
  { id: 'actions', header: '', meta: { class: { td: 'text-right' } } },
]

const formOpen = ref(false)
const editing = ref<ServiceType | null>(null)
function openCreate() {
  editing.value = null
  formOpen.value = true
}
function openEdit(type: ServiceType) {
  editing.value = type
  formOpen.value = true
}

const deleteTarget = ref<ServiceType | null>(null)
const deleteOpen = ref(false)
const deleteBusy = ref(false)
const deleteError = ref<string | null>(null)
function openDelete(type: ServiceType) {
  deleteTarget.value = type
  deleteError.value = null
  deleteOpen.value = true
}
async function confirmDelete() {
  if (!deleteTarget.value) {
    return
  }
  deleteBusy.value = true
  deleteError.value = null
  try {
    await client(`/api/v1/admin/service-types/${deleteTarget.value.id}`, { method: 'delete' })
    deleteOpen.value = false
    toast.add({ title: `Service type ${deleteTarget.value.type} deleted`, color: 'success', icon: 'i-lucide-trash-2' })
    load()
  }
  catch (e) {
    deleteError.value = parseApiError(e).message
  }
  finally {
    deleteBusy.value = false
  }
}

function rowActions(type: ServiceType): DropdownMenuItem[][] {
  const groups: DropdownMenuItem[][] = []
  if (can('service-types.update')) {
    groups.push([{ label: 'Edit', icon: 'i-lucide-pencil', onSelect: () => openEdit(type) }])
  }
  if (can('service-types.delete')) {
    groups.push([{ label: 'Delete', icon: 'i-lucide-trash-2', color: 'error', onSelect: () => openDelete(type) }])
  }
  return groups
}
</script>

<template>
  <UDashboardPanel id="service-types">
    <template #header>
      <UDashboardNavbar title="Service types">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="can('service-types.create')"
            icon="i-lucide-plus"
            @click="openCreate"
          >
            Add service type
          </UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UAlert
        v-if="error"
        color="error"
        variant="subtle"
        icon="i-lucide-circle-alert"
        :title="error"
        class="mb-4"
      />

      <UTable
        :data="rows"
        :columns="columns"
        :loading="loading"
        empty="No service types yet."
        :ui="{ thead: '[&>tr]:bg-elevated/50', td: 'border-b border-default' }"
      >
        <template #type-cell="{ row }">
          <span class="font-medium text-highlighted">{{ row.original.type }}</span>
        </template>
        <template #description-cell="{ row }">
          <span class="line-clamp-2 text-sm text-muted">{{ row.original.description || '—' }}</span>
        </template>
        <template #services_count-cell="{ row }">
          <span class="tabular-nums">{{ row.original.services_count }}</span>
        </template>
        <template #actions-cell="{ row }">
          <UDropdownMenu
            v-if="rowActions(row.original).length"
            :items="rowActions(row.original)"
            :content="{ align: 'end' }"
          >
            <UButton
              icon="i-lucide-ellipsis-vertical"
              color="neutral"
              variant="ghost"
              :aria-label="`Actions for ${row.original.type}`"
            />
          </UDropdownMenu>
        </template>
      </UTable>

      <AdminServiceTypeFormModal
        v-model:open="formOpen"
        :service-type="editing"
        @saved="load"
      />

      <AdminConfirmModal
        v-model:open="deleteOpen"
        title="Delete service type?"
        :description="`${deleteTarget?.type} will be removed. A type still used by services cannot be deleted.`"
        confirm-label="Delete service type"
        danger
        :busy="deleteBusy"
        :error="deleteError"
        @confirm="confirmDelete"
      />
    </template>
  </UDashboardPanel>
</template>
