<script setup lang="ts">
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui'
import type { Permission } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

definePageMeta({ middleware: ['sanctum:auth', 'permission'], permission: 'permissions.view' })
useHead({ title: 'Permissions · CSMF-MRS' })

const client = useSanctumClient()
const { can } = useCurrentUser()
const toast = useToast()

const { query, rows, total, loading, error, load } = useAdminList<Permission>('/api/v1/admin/permissions', { sort: 'title', per_page: 50 })
onMounted(load)

const columns: TableColumn<Permission>[] = [
  { accessorKey: 'title', header: 'Permission' },
  { accessorKey: 'description', header: 'What it allows' },
  { accessorKey: 'roles_count', header: 'Roles', meta: { class: { th: 'text-right', td: 'text-right' } } },
  { id: 'actions', header: '', meta: { class: { td: 'text-right' } } },
]

const formOpen = ref(false)
const editing = ref<Permission | null>(null)
function openCreate() {
  editing.value = null
  formOpen.value = true
}
function openEdit(permission: Permission) {
  editing.value = permission
  formOpen.value = true
}

const deleteTarget = ref<Permission | null>(null)
const deleteOpen = ref(false)
const deleteBusy = ref(false)
const deleteError = ref<string | null>(null)
function openDelete(permission: Permission) {
  deleteTarget.value = permission
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
    await client(`/api/v1/admin/permissions/${deleteTarget.value.id}`, { method: 'delete' })
    deleteOpen.value = false
    toast.add({ title: `Permission ${deleteTarget.value.title} deleted`, color: 'success', icon: 'i-lucide-trash-2' })
    load()
  }
  catch (e) {
    deleteError.value = parseApiError(e).message
  }
  finally {
    deleteBusy.value = false
  }
}

function rowActions(permission: Permission): DropdownMenuItem[][] {
  const groups: DropdownMenuItem[][] = []
  if (can('permissions.update')) {
    groups.push([{ label: 'Edit', icon: 'i-lucide-pencil', onSelect: () => openEdit(permission) }])
  }
  if (can('permissions.delete') && !permission.is_protected) {
    groups.push([{ label: 'Delete', icon: 'i-lucide-trash-2', color: 'error', onSelect: () => openDelete(permission) }])
  }
  return groups
}
</script>

<template>
  <UDashboardPanel id="permissions">
    <template #header>
      <UDashboardNavbar title="Permissions">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="can('permissions.create')"
            icon="i-lucide-plus"
            @click="openCreate"
          >
            Add permission
          </UButton>
        </template>
      </UDashboardNavbar>
      <UDashboardToolbar>
        <div class="flex w-full flex-wrap items-center gap-2 py-1">
          <UInput
            id="permissions-search"
            v-model="query.q"
            icon="i-lucide-search"
            placeholder="Search title or description"
            class="w-full sm:w-72"
            aria-label="Search permissions"
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
        empty="No permissions match this search."
        :ui="{ thead: '[&>tr]:bg-elevated/50', td: 'border-b border-default' }"
      >
        <template #title-cell="{ row }">
          <div class="flex items-center gap-2">
            <code class="font-mono text-sm text-highlighted">{{ row.original.title }}</code>
            <UBadge
              v-if="row.original.is_protected"
              color="neutral"
              variant="outline"
              size="sm"
              icon="i-lucide-lock"
            >
              System
            </UBadge>
          </div>
        </template>
        <template #description-cell="{ row }">
          <span class="text-sm text-muted">{{ row.original.description || '—' }}</span>
        </template>
        <template #roles_count-cell="{ row }">
          <span class="tabular-nums">{{ row.original.roles_count }}</span>
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
              :aria-label="`Actions for ${row.original.title}`"
            />
          </UDropdownMenu>
        </template>
      </UTable>

      <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-default pt-4">
        <span class="text-sm text-muted tabular-nums">{{ total }} {{ total === 1 ? 'permission' : 'permissions' }}</span>
        <UPagination
          v-model:page="query.page"
          :items-per-page="query.per_page"
          :total="total"
        />
      </div>

      <AdminPermissionFormModal
        v-model:open="formOpen"
        :permission="editing"
        @saved="load"
      />

      <AdminConfirmModal
        v-model:open="deleteOpen"
        title="Delete permission?"
        :description="`${deleteTarget?.title} will be removed from every role.`"
        confirm-label="Delete permission"
        danger
        :busy="deleteBusy"
        :error="deleteError"
        @confirm="confirmDelete"
      />
    </template>
  </UDashboardPanel>
</template>
