<script setup lang="ts">
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui'
import type { Service } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

definePageMeta({ middleware: ['sanctum:auth', 'permission'], permission: 'services.view' })
useHead({ title: 'Services · CSMF-MRS' })

/** The first Citizen's Charter loaded into the system. */
const FIRST_CHARTER_YEAR = 2026

const client = useSanctumClient()
const route = useRoute()
const { user: me, can } = useCurrentUser()
const toast = useToast()
const { offices, serviceTypes, loadOffices, loadServiceTypes } = useOptions()

// Same rule as User::scopedOfficeId() on the API.
const lockedOfficeId = computed(() => (me.value?.is_system_administrator ? null : me.value?.office?.id ?? null))

const initialOffice = Number(route.query.office_id)
const { query, rows, total, loading, error, load } = useAdminList<Service>('/api/v1/admin/services', {
  sort: 'sort_order',
  office_id: Number.isInteger(initialOffice) && initialOffice > 0 ? initialOffice : undefined,
  service_type_id: undefined,
  charter_year: undefined,
  status: undefined,
})

onMounted(() => {
  load()
  loadOffices()
  loadServiceTypes()
})

function optionalNumber(key: 'office_id' | 'service_type_id' | 'charter_year') {
  return computed({
    get: () => (query[key] === undefined ? 'all' : String(query[key])),
    set: (value: string) => { query[key] = value === 'all' ? undefined : Number(value) },
  })
}
const officeFilter = optionalNumber('office_id')
const typeFilter = optionalNumber('service_type_id')
const yearFilter = optionalNumber('charter_year')
const statusFilter = computed({
  get: () => (query.status as string | undefined) ?? 'all',
  set: (value: string) => { query.status = value === 'all' ? undefined : value },
})

const officeFilterItems = computed(() => [{ label: 'All offices', value: 'all' }, ...offices.value.map(o => ({ label: o.code, value: String(o.id) }))])
const typeFilterItems = computed(() => [{ label: 'All types', value: 'all' }, ...serviceTypes.value.map(t => ({ label: t.type, value: String(t.id) }))])
const yearFilterItems = computed(() => {
  const latest = Math.max(new Date().getFullYear() + 1, FIRST_CHARTER_YEAR)
  const years = Array.from({ length: latest - FIRST_CHARTER_YEAR + 1 }, (_, i) => latest - i)
  return [{ label: 'Any year', value: 'all' }, ...years.map(y => ({ label: String(y), value: String(y) }))]
})
const statusItems = [
  { label: 'Any status', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]
const sortItems = [
  { label: 'Charter order', value: 'sort_order' },
  { label: 'Name A–Z', value: 'name' },
  { label: 'Newest charter', value: '-charter_year' },
]

const columns: TableColumn<Service>[] = [
  { id: 'office', header: 'Office' },
  { accessorKey: 'name', header: 'Service' },
  { id: 'type', header: 'Type' },
  { accessorKey: 'charter_year', header: 'Charter' },
  { id: 'status', header: 'Status' },
  { id: 'actions', header: '', meta: { class: { td: 'text-right' } } },
]

const formOpen = ref(false)
const editing = ref<Service | null>(null)
function openCreate() {
  editing.value = null
  formOpen.value = true
}
function openEdit(service: Service) {
  editing.value = service
  formOpen.value = true
}

const deleteTarget = ref<Service | null>(null)
const deleteOpen = ref(false)
const deleteBusy = ref(false)
const deleteError = ref<string | null>(null)
function openDelete(service: Service) {
  deleteTarget.value = service
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
    await client(`/api/v1/admin/services/${deleteTarget.value.id}`, { method: 'delete' })
    deleteOpen.value = false
    toast.add({ title: 'Service deleted', color: 'success', icon: 'i-lucide-trash-2' })
    load()
  }
  catch (e) {
    deleteError.value = parseApiError(e).message
  }
  finally {
    deleteBusy.value = false
  }
}

function rowActions(service: Service): DropdownMenuItem[][] {
  const groups: DropdownMenuItem[][] = []
  if (can('services.update')) {
    groups.push([{ label: 'Edit', icon: 'i-lucide-pencil', onSelect: () => openEdit(service) }])
  }
  if (can('services.delete')) {
    groups.push([{ label: 'Delete', icon: 'i-lucide-trash-2', color: 'error', onSelect: () => openDelete(service) }])
  }
  return groups
}
</script>

<template>
  <UDashboardPanel id="services">
    <template #header>
      <UDashboardNavbar title="Services">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="can('services.create')"
            icon="i-lucide-plus"
            @click="openCreate"
          >
            Add service
          </UButton>
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <div class="flex w-full flex-wrap items-center gap-2 py-1">
          <UInput
            id="services-search"
            v-model="query.q"
            icon="i-lucide-search"
            placeholder="Search services"
            class="w-full sm:w-64"
            aria-label="Search services"
          />
          <USelectMenu
            v-if="lockedOfficeId === null"
            id="services-office-filter"
            v-model="officeFilter"
            :items="officeFilterItems"
            value-key="value"
            class="w-44"
            aria-label="Filter by office"
          />
          <USelect
            id="services-type-filter"
            v-model="typeFilter"
            :items="typeFilterItems"
            class="w-36"
            aria-label="Filter by type"
          />
          <USelect
            id="services-year-filter"
            v-model="yearFilter"
            :items="yearFilterItems"
            class="w-32"
            aria-label="Filter by charter year"
          />
          <USelect
            id="services-status-filter"
            v-model="statusFilter"
            :items="statusItems"
            class="w-36"
            aria-label="Filter by status"
          />
          <USelect
            id="services-sort"
            v-model="query.sort"
            :items="sortItems"
            class="w-40"
            aria-label="Sort"
          />
        </div>
      </UDashboardToolbar>
    </template>

    <template #body>
      <UAlert
        v-if="lockedOfficeId !== null"
        color="info"
        variant="subtle"
        icon="i-lucide-building-2"
        :title="`Showing services of ${me?.office?.code} only`"
        description="Your account is assigned to this office."
        class="mb-4"
      />
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
        empty="No services match these filters."
        :ui="{ thead: '[&>tr]:bg-elevated/50', td: 'border-b border-default whitespace-normal' }"
      >
        <template #office-cell="{ row }">
          <span
            class="font-mono text-sm"
            :title="row.original.office?.name"
          >{{ row.original.office?.code }}</span>
        </template>
        <template #name-cell="{ row }">
          <span class="text-highlighted">{{ row.original.name }}</span>
        </template>
        <template #type-cell="{ row }">
          <UBadge
            color="neutral"
            variant="outline"
          >
            {{ row.original.service_type?.type }}
          </UBadge>
        </template>
        <template #charter_year-cell="{ row }">
          <span class="tabular-nums text-muted">{{ row.original.charter_year }}</span>
        </template>
        <template #status-cell="{ row }">
          <UBadge
            :color="row.original.is_active ? 'success' : 'neutral'"
            variant="subtle"
          >
            {{ row.original.is_active ? 'Active' : 'Inactive' }}
          </UBadge>
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
              :aria-label="`Actions for ${row.original.name}`"
            />
          </UDropdownMenu>
        </template>
      </UTable>

      <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-default pt-4">
        <span class="text-sm text-muted tabular-nums">{{ total }} {{ total === 1 ? 'service' : 'services' }}</span>
        <UPagination
          v-model:page="query.page"
          :items-per-page="query.per_page"
          :total="total"
        />
      </div>

      <AdminServiceFormModal
        v-model:open="formOpen"
        :service="editing"
        :office-options="offices"
        :service-type-options="serviceTypes"
        :locked-office-id="lockedOfficeId"
        :default-office-id="typeof query.office_id === 'number' ? query.office_id : undefined"
        @saved="load"
      />

      <AdminConfirmModal
        v-model:open="deleteOpen"
        title="Delete service?"
        :description="`${deleteTarget?.name} will be removed. A service that feedback already refers to cannot be deleted; deactivate it instead.`"
        confirm-label="Delete service"
        danger
        :busy="deleteBusy"
        :error="deleteError"
        @confirm="confirmDelete"
      />
    </template>
  </UDashboardPanel>
</template>
