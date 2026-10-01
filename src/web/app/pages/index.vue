<script setup lang="ts">
definePageMeta({ middleware: ['sanctum:auth'] })
useHead({ title: 'Home · CSMF-MRS' })

const { user, can } = useCurrentUser()

const shortcuts = computed(() => [
  { label: 'Users', description: 'Add staff accounts, assign roles, reset passwords.', icon: 'i-lucide-users', to: '/admin/users', permission: 'users.view' },
  { label: 'Roles', description: 'Decide what each role can see and do.', icon: 'i-lucide-shield', to: '/admin/roles', permission: 'roles.view' },
  { label: 'Permissions', description: 'Review the permission catalog.', icon: 'i-lucide-key-round', to: '/admin/permissions', permission: 'permissions.view' },
].filter(item => can(item.permission)))
</script>

<template>
  <UDashboardPanel id="home">
    <template #header>
      <UDashboardNavbar title="Home">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex max-w-4xl flex-col gap-6">
        <div class="flex flex-col gap-1">
          <h1 class="text-2xl font-semibold text-highlighted text-balance">
            Welcome, {{ user?.name }}
          </h1>
          <p class="text-muted">
            The dashboard with feedback figures arrives once offices, services and the feedback form are in place.
          </p>
        </div>

        <div
          v-if="shortcuts.length"
          class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
          <UPageCard
            v-for="item in shortcuts"
            :key="item.to"
            :title="item.label"
            :description="item.description"
            :icon="item.icon"
            :to="item.to"
            variant="subtle"
          />
        </div>

        <AppApiStatusCard class="max-w-xl" />
      </div>
    </template>
  </UDashboardPanel>
</template>
