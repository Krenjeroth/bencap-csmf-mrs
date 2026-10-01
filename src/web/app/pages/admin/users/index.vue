<script setup lang="ts">
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui'
import type { RoleSummary, User } from '~/types/api'
import { parseApiError } from '~/utils/apiError'

definePageMeta({ middleware: ['sanctum:auth', 'permission'], permission: 'users.view' })
useHead({ title: 'Users · CSMF-MRS' })

const client = useSanctumClient()
const { user: me, can } = useCurrentUser()
const toast = useToast()

const { query, rows, total, loading, error, load } = useAdminList<User>('/api/v1/admin/users', { sort: 'name', role_id: undefined, status: undefined })

const roleOptions = ref<RoleSummary[]>([])
onMounted(async () => {
  load()
  try {
    roleOptions.value = (await client<{ data: RoleSummary[] }>('/api/v1/admin/role-options')).data
  }
  catch (e) {
    toast.add({ title: parseApiError(e).message, color: 'error' })
  }
})

const roleFilterItems = computed(() => [{ label: 'All roles', value: 'all' }, ...roleOptions.value.map(r => ({ label: r.title, value: String(r.id) }))])
const roleFilter = computed({
  get: () => (query.role_id === undefined ? 'all' : String(query.role_id)),
  set: (value: string) => { query.role_id = value === 'all' ? undefined : Number(value) },
})
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
  { label: 'Name A–Z', value: 'name' },
  { label: 'Name Z–A', value: '-name' },
  { label: 'Newest first', value: '-created_at' },
  { label: 'Last sign-in', value: '-last_login_at' },
]

const columns: TableColumn<User>[] = [
  { accessorKey: 'name', header: 'User' },
  { id: 'roles', header: 'Roles' },
  { id: 'status', header: 'Status' },
  { id: 'two_factor', header: 'Two-factor' },
  { accessorKey: 'last_login_at', header: 'Last sign-in' },
  { id: 'actions', header: '', meta: { class: { td: 'text-right' } } },
]

function formatDate(value: string | null) {
  return value ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : 'Never'
}

// Create / edit
const formOpen = ref(false)
const editing = ref<User | null>(null)
function openCreate() {
  editing.value = null
  formOpen.value = true
}
function openEdit(user: User) {
  editing.value = user
  formOpen.value = true
}

// Reset password
const resetTarget = ref<User | null>(null)
const resetOpen = ref(false)
const resetTwoFactor = ref(false)
const resetBusy = ref(false)
const resetError = ref<string | null>(null)
const resetResult = ref<{ email: string, password: string } | null>(null)
function openReset(user: User) {
  resetTarget.value = user
  resetTwoFactor.value = false
  resetError.value = null
  resetResult.value = null
  resetOpen.value = true
}
async function confirmReset() {
  if (!resetTarget.value) {
    return
  }
  resetBusy.value = true
  resetError.value = null
  try {
    const response = await client<{ temporary_password: string }>(`/api/v1/admin/users/${resetTarget.value.id}/reset-password`, {
      method: 'post',
      body: { reset_two_factor: resetTwoFactor.value },
    })
    resetResult.value = { email: resetTarget.value.email, password: response.temporary_password }
    load()
  }
  catch (e) {
    resetError.value = parseApiError(e).message
  }
  finally {
    resetBusy.value = false
  }
}

// Delete
const deleteTarget = ref<User | null>(null)
const deleteOpen = ref(false)
const deleteBusy = ref(false)
const deleteError = ref<string | null>(null)
function openDelete(user: User) {
  deleteTarget.value = user
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
    await client(`/api/v1/admin/users/${deleteTarget.value.id}`, { method: 'delete' })
    deleteOpen.value = false
    toast.add({ title: `${deleteTarget.value.name} was deleted`, color: 'success', icon: 'i-lucide-trash-2' })
    load()
  }
  catch (e) {
    deleteError.value = parseApiError(e).message
  }
  finally {
    deleteBusy.value = false
  }
}

function rowActions(user: User): DropdownMenuItem[][] {
  const isSelf = user.id === me.value?.id
  const groups: DropdownMenuItem[][] = []
  if (can('users.update')) {
    groups.push([
      { label: 'Edit', icon: 'i-lucide-pencil', onSelect: () => openEdit(user) },
      { label: 'Reset password', icon: 'i-lucide-key-round', disabled: isSelf, onSelect: () => openReset(user) },
    ])
  }
  if (can('users.delete')) {
    groups.push([{ label: 'Delete', icon: 'i-lucide-trash-2', color: 'error', disabled: isSelf, onSelect: () => openDelete(user) }])
  }
  return groups
}
</script>

<template>
  <UDashboardPanel id="users">
    <template #header>
      <UDashboardNavbar title="Users">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
        <template #right>
          <UButton
            v-if="can('users.create')"
            icon="i-lucide-user-plus"
            @click="openCreate"
          >
            Add user
          </UButton>
        </template>
      </UDashboardNavbar>

      <UDashboardToolbar>
        <div class="flex w-full flex-wrap items-center gap-2 py-1">
          <UInput
            id="users-search"
            v-model="query.q"
            icon="i-lucide-search"
            placeholder="Search name or email"
            class="w-full sm:w-64"
            aria-label="Search users"
          />
          <USelect
            id="users-role-filter"
            v-model="roleFilter"
            :items="roleFilterItems"
            class="w-44"
            aria-label="Filter by role"
          />
          <USelect
            id="users-status-filter"
            v-model="statusFilter"
            :items="statusItems"
            class="w-36"
            aria-label="Filter by status"
          />
          <USelect
            id="users-sort"
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
        empty="No users match these filters."
        class="shrink-0"
        :ui="{ base: 'table-fixed border-separate border-spacing-0', thead: '[&>tr]:bg-elevated/50', th: 'py-2 first:rounded-l-lg last:rounded-r-lg border-y border-default first:border-l last:border-r', td: 'border-b border-default' }"
      >
        <template #name-cell="{ row }">
          <div class="flex min-w-0 flex-col">
            <span class="truncate font-medium text-highlighted">
              {{ row.original.name }}
              <span
                v-if="row.original.id === me?.id"
                class="text-xs font-normal text-muted"
              >(you)</span>
            </span>
            <span class="truncate text-sm text-muted">{{ row.original.email }}</span>
          </div>
        </template>
        <template #roles-cell="{ row }">
          <div class="flex flex-wrap gap-1">
            <UBadge
              v-for="role in row.original.roles ?? []"
              :key="role.id"
              :color="role.is_system ? 'secondary' : 'neutral'"
              variant="subtle"
            >
              {{ role.title }}
            </UBadge>
            <span
              v-if="!row.original.roles?.length"
              class="text-sm text-dimmed"
            >None</span>
          </div>
        </template>
        <template #status-cell="{ row }">
          <div class="flex flex-wrap gap-1">
            <UBadge
              :color="row.original.is_active ? 'success' : 'neutral'"
              variant="subtle"
            >
              {{ row.original.is_active ? 'Active' : 'Inactive' }}
            </UBadge>
            <UBadge
              v-if="row.original.must_change_password"
              color="warning"
              variant="subtle"
            >
              Temporary password
            </UBadge>
          </div>
        </template>
        <template #two_factor-cell="{ row }">
          <UBadge
            :color="row.original.two_factor_enabled ? 'success' : 'neutral'"
            variant="outline"
            :icon="row.original.two_factor_enabled ? 'i-lucide-shield-check' : 'i-lucide-shield-off'"
          >
            {{ row.original.two_factor_enabled ? 'On' : 'Off' }}
          </UBadge>
        </template>
        <template #last_login_at-cell="{ row }">
          <span class="text-sm tabular-nums text-muted">{{ formatDate(row.original.last_login_at) }}</span>
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
        <span class="text-sm text-muted tabular-nums">{{ total }} {{ total === 1 ? 'user' : 'users' }}</span>
        <UPagination
          v-model:page="query.page"
          :items-per-page="query.per_page"
          :total="total"
        />
      </div>

      <AdminUserFormModal
        v-model:open="formOpen"
        :user="editing"
        :role-options="roleOptions"
        @saved="load"
      />

      <AdminConfirmModal
        v-model:open="resetOpen"
        :title="resetResult ? 'Password reset' : 'Reset password?'"
        :description="resetResult ? '' : `${resetTarget?.name} will be signed out everywhere and must choose a new password on next sign-in.`"
        :confirm-label="resetResult ? 'Done' : 'Reset password'"
        :busy="resetBusy"
        :error="resetError"
        @confirm="resetResult ? (resetOpen = false) : confirmReset()"
      >
        <AdminTemporaryPassword
          v-if="resetResult"
          :password="resetResult.password"
          :email="resetResult.email"
        />
        <UCheckbox
          v-else-if="resetTarget?.two_factor_enabled"
          id="reset-two-factor"
          v-model="resetTwoFactor"
          label="Also turn off two-factor login"
          description="Use this only when the user has lost the phone with their authenticator app."
        />
      </AdminConfirmModal>

      <AdminConfirmModal
        v-model:open="deleteOpen"
        title="Delete user?"
        :description="`${deleteTarget?.name} (${deleteTarget?.email}) will no longer be able to sign in. Their past actions stay in the audit log.`"
        confirm-label="Delete user"
        danger
        :busy="deleteBusy"
        :error="deleteError"
        @confirm="confirmDelete"
      />
    </template>
  </UDashboardPanel>
</template>
