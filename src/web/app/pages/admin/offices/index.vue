<script setup lang="ts">
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui'
import type { Office } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

definePageMeta({ middleware: ['sanctum:auth', 'permission'], permission: 'offices.view' })
useHead({ title: 'Offices · CSMF-MRS' })

const client = useSanctumClient()
const { can } = useCurrentUser()
const toast = useToast()

const { query, rows, total, loading, error, load } = useAdminList<Office>('/api/v1/admin/offices', { sort: 'sort_order', status: undefined })
onMounted(load)

const statusFilter = computed({
  get: () => (query.status as string | undefined) ?? 'all',
  set: (value: string) => { query.status = value === 'all' ? undefined : value },
})
const statusItems = [
  { label: 'Any status', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]
const sortItems = [
  { label: 'Charter order', value: 'sort_order' },
  { label: 'Code A–Z', value: 'code' },
  { label: 'Name A–Z', value: 'name' },
]

const columns: TableColumn<Office>[] = [
  { accessorKey: 'code', header: 'Code' },
  { accessorKey: 'name', header: 'Office' },
  { id: 'status', header: 'Status' },
  { accessorKey: 'services_count', header: 'Services', meta: { class: { th: 'text-right', td: 'text-right' } } },
  { accessorKey: 'users_count', header: 'Users', meta: { class: { th: 'text-right', td: 'text-right' } } },
  { id: 'actions', header: '', meta: { class: { td: 'text-right' } } },
]

const formOpen = ref(false)
const editing = ref<Office | null>(null)
function openCreate() {
  editing.value = null
  formOpen.value = true
}
function openEdit(office: Office) {
  editing.value = office
  formOpen.value = true
}

const deleteTarget = ref<Office | null>(null)
const deleteOpen = ref(false)
const deleteBusy = ref(false)
const deleteError = ref<string | null>(null)
function openDelete(office: Office) {
  deleteTarget.value = office
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
    await client(`/api/v1/admin/offices/${deleteTarget.value.id}`, { method: 'delete' })
    deleteOpen.value = false
    toast.add({ title: `Office ${deleteTarget.value.code} deleted`, color: 'success', icon: 'i-lucide-trash-2' })
    load()
  }
  catch (e) {
    deleteError.value = parseApiError(e).message
  }
  finally {
    deleteBusy.value = false
  }
}

function rowActions(office: Office): DropdownMenuItem[][] {
  const groups: DropdownMenuItem[][] = []
  const view: DropdownMenuItem[] = []
  if (can('offices.update')) {
    view.push({ label: 'Edit', icon: 'i-lucide-pencil', onSelect: () => openEdit(office) })
  }
  if (can('services.view')) {
    view.push({ label: 'View services', icon: 'i-lucide-list-checks', to: { path: '/admin/services', query: { office_id: office.id } } })
  }
  if (view.length) {
    groups.push(view)
  }
  if (can('offices.delete')) {
    groups.push([{ label: 'Delete', icon: 'i-lucide-trash-2', color: 'error', onSelect: () => openDelete(office) }])
  }
  return groups
}
</script>

<template>
  <UDashboardPanel id="offices">
    <template #header>
      <UDashboardNavbar title="Offices">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="can('offices.create')"
            icon="i-lucide-plus"
            @click="openCreate"
          >
            Add office
          </UButton>
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <div class="flex w-full flex-wrap items-center gap-2 py-1">
          <UInput
            id="offices-search"
            v-model="query.q"
            icon="i-lucide-search"
            placeholder="Search code or name"
            class="w-full sm:w-64"
            aria-label="Search offices"
          />
          <USelect
            id="offices-status-filter"
            v-model="statusFilter"
            :items="statusItems"
            class="w-36"
            aria-label="Filter by status"
          />
          <USelect
            id="offices-sort"
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
        empty="No offices match these filters."
        :ui="{ thead: '[&>tr]:bg-elevated/50', td: 'border-b border-default' }"
      >
        <template #code-cell="{ row }">
          <span class="font-mono text-sm font-medium text-highlighted">{{ row.original.code }}</span>
        </template>
        <template #name-cell="{ row }">
          <div class="flex min-w-0 flex-col">
            <span class="truncate">{{ row.original.name }}</span>
            <span class="truncate font-mono text-xs text-muted">/f/{{ row.original.slug }}</span>
          </div>
        </template>
        <template #status-cell="{ row }">
          <UBadge
            :color="row.original.is_active ? 'success' : 'neutral'"
            variant="subtle"
          >
            {{ row.original.is_active ? 'Active' : 'Inactive' }}
          </UBadge>
        </template>
        <template #services_count-cell="{ row }">
          <span class="tabular-nums">
            {{ row.original.active_services_count ?? row.original.services_count }}
            <span
              v-if="row.original.active_services_count !== undefined && row.original.active_services_count !== row.original.services_count"
              class="text-xs text-muted"
            >of {{ row.original.services_count }}</span>
          </span>
        </template>
        <template #users_count-cell="{ row }">
          <span class="tabular-nums">{{ row.original.users_count }}</span>
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
              :aria-label="`Actions for ${row.original.code}`"
            />
          </UDropdownMenu>
        </template>
      </UTable>

      <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-default pt-4">
        <span class="text-sm text-muted tabular-nums">{{ total }} {{ total === 1 ? 'office' : 'offices' }}</span>
        <UPagination
          v-model:page="query.page"
          :items-per-page="query.per_page"
          :total="total"
        />
      </div>

      <AdminOfficeFormModal
        v-model:open="formOpen"
        :office="editing"
        @saved="load"
      />

      <AdminConfirmModal
        v-model:open="deleteOpen"
        title="Delete office?"
        :description="`${deleteTarget?.code} – ${deleteTarget?.name} will be removed. An office with services or user accounts cannot be deleted; deactivate it instead.`"
        confirm-label="Delete office"
        danger
        :busy="deleteBusy"
        :error="deleteError"
        @confirm="confirmDelete"
      />
    </template>
  </UDashboardPanel>
</template>
