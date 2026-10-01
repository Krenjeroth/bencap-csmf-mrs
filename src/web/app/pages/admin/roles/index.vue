<script setup lang="ts">
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui'
import type { PermissionOption, Role } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

definePageMeta({ middleware: ['sanctum:auth', 'permission'], permission: 'roles.view' })
useHead({ title: 'Roles · CSMF-MRS' })

const client = useSanctumClient()
const { can } = useCurrentUser()
const toast = useToast()

const { query, rows, total, loading, error, load } = useAdminList<Role>('/api/v1/admin/roles', { sort: 'title' })

const permissionOptions = ref<PermissionOption[]>([])
onMounted(async () => {
  load()
  try {
    permissionOptions.value = (await client<{ data: PermissionOption[] }>('/api/v1/admin/permission-options')).data
  }
  catch (e) {
    toast.add({ title: parseApiError(e).message, color: 'error' })
  }
})

const columns: TableColumn<Role>[] = [
  { accessorKey: 'title', header: 'Role' },
  { accessorKey: 'description', header: 'Description' },
  { accessorKey: 'permissions_count', header: 'Permissions', meta: { class: { th: 'text-right', td: 'text-right' } } },
  { accessorKey: 'users_count', header: 'Users', meta: { class: { th: 'text-right', td: 'text-right' } } },
  { id: 'actions', header: '', meta: { class: { td: 'text-right' } } },
]

const formOpen = ref(false)
const editing = ref<Role | null>(null)
function openCreate() {
  editing.value = null
  formOpen.value = true
}
function openEdit(role: Role) {
  editing.value = role
  formOpen.value = true
}

const deleteTarget = ref<Role | null>(null)
const deleteOpen = ref(false)
const deleteBusy = ref(false)
const deleteError = ref<string | null>(null)
function openDelete(role: Role) {
  deleteTarget.value = role
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
    await client(`/api/v1/admin/roles/${deleteTarget.value.id}`, { method: 'delete' })
    deleteOpen.value = false
    toast.add({ title: `Role "${deleteTarget.value.title}" deleted`, color: 'success', icon: 'i-lucide-trash-2' })
    load()
  }
  catch (e) {
    deleteError.value = parseApiError(e).message
  }
  finally {
    deleteBusy.value = false
  }
}

function rowActions(role: Role): DropdownMenuItem[][] {
  const groups: DropdownMenuItem[][] = [[{
    label: can('roles.update') ? 'Edit' : 'View permissions',
    icon: can('roles.update') ? 'i-lucide-pencil' : 'i-lucide-eye',
    onSelect: () => openEdit(role),
  }]]
  if (can('roles.delete') && !role.is_system) {
    groups.push([{ label: 'Delete', icon: 'i-lucide-trash-2', color: 'error', onSelect: () => openDelete(role) }])
  }
  return groups
}
</script>

<template>
  <UDashboardPanel id="roles">
    <template #header>
      <UDashboardNavbar title="Roles">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="can('roles.create')"
            icon="i-lucide-plus"
            @click="openCreate"
          >
            Add role
          </UButton>
        </template>
      </UDashboardNavbar>
      <UDashboardToolbar>
        <div class="flex w-full flex-wrap items-center gap-2 py-1">
          <UInput
            id="roles-search"
            v-model="query.q"
            icon="i-lucide-search"
            placeholder="Search roles"
            class="w-full sm:w-64"
            aria-label="Search roles"
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
        empty="No roles match this search."
        :ui="{ thead: '[&>tr]:bg-elevated/50', td: 'border-b border-default' }"
      >
        <template #title-cell="{ row }">
          <div class="flex items-center gap-2">
            <span class="font-medium text-highlighted">{{ row.original.title }}</span>
            <UBadge
              v-if="row.original.is_system"
              color="secondary"
              variant="subtle"
              size="sm"
            >
              System
            </UBadge>
          </div>
        </template>
        <template #description-cell="{ row }">
          <span class="line-clamp-2 text-sm text-muted">{{ row.original.description || '—' }}</span>
        </template>
        <template #permissions_count-cell="{ row }">
          <span class="tabular-nums">{{ row.original.permissions_count }}</span>
        </template>
        <template #users_count-cell="{ row }">
          <span class="tabular-nums">{{ row.original.users_count }}</span>
        </template>
        <template #actions-cell="{ row }">
          <UDropdownMenu
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
        <span class="text-sm text-muted tabular-nums">{{ total }} {{ total === 1 ? 'role' : 'roles' }}</span>
        <UPagination
          v-model:page="query.page"
          :items-per-page="query.per_page"
          :total="total"
        />
      </div>

      <AdminRoleFormModal
        v-model:open="formOpen"
        :role="editing"
        :permission-options="permissionOptions"
        @saved="load"
      />

      <AdminConfirmModal
        v-model:open="deleteOpen"
        title="Delete role?"
        :description="`The role ${deleteTarget?.title} will be removed. Roles still assigned to users cannot be deleted.`"
        confirm-label="Delete role"
        danger
        :busy="deleteBusy"
        :error="deleteError"
        @confirm="confirmDelete"
      />
    </template>
  </UDashboardPanel>
</template>
