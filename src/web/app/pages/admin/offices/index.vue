<script setup lang="ts">
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui'
import type { Office } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

definePageMeta({ middleware: ['sanctum:auth', 'permission'], permission: 'offices.view' })
useHead({ title: 'Offices · CSMF-MRS' })

const client = useSanctumClient()
const { can } = useCurrentUser()
const toast = useToast()

const { query, rows, total, loading, error, load } = useAdminList<Office>('/api/v1/admin/offices', { sort: 'sort_order', status: undefined, per_page: 100 })
const { offices: officeOptions, loadOffices } = useOptions()
onMounted(() => {
  load()
  loadOffices()
})

/** After a save: the list, and the parent picker's options. */
function reload() {
  load()
  loadOffices()
}

// The tree shows every office on one page (the API allows up to 100 per
// page; the charter has 36 offices), each child right under its parent.
// The hierarchy is one level deep, so rows are flattened here rather than
// using the table's expandable rows (which add an empty row per parent).
type OfficeRow = Office & { depth: 0 | 1, childCount: number }
const view = ref<'tree' | 'list'>('tree')
const collapsed = ref(new Set<number>())
watch(view, (value) => {
  query.per_page = value === 'tree' ? 100 : 15
  collapsed.value = new Set()
})
function toggle(office: OfficeRow) {
  const next = new Set(collapsed.value)
  if (!next.delete(office.id)) {
    next.add(office.id)
  }
  collapsed.value = next
}

const tableRows = computed<OfficeRow[]>(() => {
  if (view.value === 'list') {
    return rows.value.map(office => ({ ...office, depth: 0, childCount: 0 }))
  }
  const listed = new Set(rows.value.map(office => office.id))
  const children = new Map<number, Office[]>()
  for (const office of rows.value) {
    if (office.parent_id !== null && listed.has(office.parent_id)) {
      children.set(office.parent_id, [...(children.get(office.parent_id) ?? []), office])
    }
  }
  // A child whose parent is filtered out shows at the top level.
  return rows.value
    .filter(office => office.parent_id === null || !listed.has(office.parent_id))
    .flatMap((office) => {
      const kids = children.get(office.id) ?? []
      const parent: OfficeRow = { ...office, depth: 0, childCount: kids.length }
      return collapsed.value.has(office.id)
        ? [parent]
        : [parent, ...kids.map(kid => ({ ...kid, depth: 1 as const, childCount: 0 }))]
    })
})
const viewItems = [
  { label: 'Tree', value: 'tree', icon: 'i-lucide-list-tree' },
  { label: 'List', value: 'list', icon: 'i-lucide-list' },
]

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

const columns: TableColumn<OfficeRow>[] = [
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
  const primary: DropdownMenuItem[] = []
  if (can('offices.update')) {
    primary.push({ label: 'Edit', icon: 'i-lucide-pencil', onSelect: () => openEdit(office) })
  }
  if (can('services.view')) {
    primary.push({ label: 'View services', icon: 'i-lucide-list-checks', to: { path: '/admin/services', query: { office_id: office.id } } })
  }
  if (primary.length) {
    groups.push(primary)
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
          <UTabs
            v-model="view"
            :items="viewItems"
            :content="false"
            size="xs"
            class="w-auto"
            aria-label="View"
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
        :data="tableRows"
        :columns="columns"
        :loading="loading"
        empty="No offices match these filters."
        :ui="{ thead: '[&>tr]:bg-elevated/50', td: 'border-b border-default' }"
      >
        <template #code-cell="{ row }">
          <div
            class="flex items-center gap-1"
            :style="{ paddingLeft: `${row.original.depth * 1.75}rem` }"
          >
            <UButton
              v-if="row.original.childCount > 0"
              :icon="collapsed.has(row.original.id) ? 'i-lucide-chevron-right' : 'i-lucide-chevron-down'"
              color="neutral"
              variant="ghost"
              size="xs"
              :aria-label="`${collapsed.has(row.original.id) ? 'Expand' : 'Collapse'} ${row.original.code}`"
              :aria-expanded="!collapsed.has(row.original.id)"
              @click="toggle(row.original)"
            />
            <span
              v-else-if="view === 'tree'"
              class="inline-block w-6"
              aria-hidden="true"
            />
            <span class="font-mono text-sm font-medium text-highlighted">{{ row.original.code }}</span>
            <UBadge
              v-if="row.original.childCount > 0"
              color="neutral"
              variant="soft"
              size="sm"
              class="tabular-nums"
              :aria-label="`${row.original.childCount} offices under ${row.original.code}`"
            >
              {{ row.original.childCount }}
            </UBadge>
          </div>
        </template>
        <template #name-cell="{ row }">
          <div class="flex min-w-0 flex-col">
            <span class="truncate">{{ row.original.name }}</span>
            <span class="truncate font-mono text-xs text-muted">
              /f/{{ row.original.slug }}
              <template v-if="view === 'list' && row.original.parent"> · under {{ row.original.parent.code }}</template>
            </span>
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
        :office-options="officeOptions"
        @saved="reload"
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
