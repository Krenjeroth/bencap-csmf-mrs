<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const { can } = useCurrentUser()

interface NavLink extends NavigationMenuItem {
  permission?: string
}

const sections: { label: string, links: NavLink[] }[] = [
  {
    label: 'Overview',
    links: [
      { label: 'Home', icon: 'i-lucide-house', to: '/' },
    ],
  },
  {
    label: 'Service catalog',
    links: [
      { label: 'Offices', icon: 'i-lucide-building-2', to: '/admin/offices', permission: 'offices.view' },
      { label: 'Services', icon: 'i-lucide-list-checks', to: '/admin/services', permission: 'services.view' },
      { label: 'Service types', icon: 'i-lucide-tags', to: '/admin/service-types', permission: 'service-types.view' },
    ],
  },
  {
    label: 'Access control',
    links: [
      { label: 'Users', icon: 'i-lucide-users', to: '/admin/users', permission: 'users.view' },
      { label: 'Roles', icon: 'i-lucide-shield', to: '/admin/roles', permission: 'roles.view' },
      { label: 'Permissions', icon: 'i-lucide-key-round', to: '/admin/permissions', permission: 'permissions.view' },
    ],
  },
]

// Each section becomes one navigation group; sections with nothing visible are dropped.
const navigation = computed<NavigationMenuItem[][]>(() => sections
  .map(section => ({
    label: section.label,
    links: section.links
      .filter(link => !link.permission || can(link.permission))
      .map(({ permission: _permission, ...link }) => link),
  }))
  .filter(section => section.links.length > 0)
  .map(section => [{ label: section.label, type: 'label' as const }, ...section.links]))
</script>

<template>
  <UDashboardGroup unit="rem">
    <UDashboardSidebar
      collapsible
      resizable
      class="bg-elevated/25"
      :ui="{ footer: 'lg:border-t lg:border-default' }"
    >
      <template #header="{ collapsed }">
        <NuxtLink
          to="/"
          class="flex items-center gap-2 overflow-hidden"
          aria-label="CSMF-MRS home"
        >
          <UIcon
            name="i-lucide-clipboard-check"
            class="size-6 shrink-0 text-primary"
          />
          <span
            v-if="!collapsed"
            class="truncate font-semibold text-highlighted"
          >CSMF-MRS</span>
        </NuxtLink>
      </template>

      <template #default="{ collapsed }">
        <UNavigationMenu
          :collapsed="collapsed"
          :items="navigation"
          orientation="vertical"
          tooltip
        />
      </template>

      <template #footer="{ collapsed }">
        <AppUserMenu :collapsed="collapsed" />
      </template>
    </UDashboardSidebar>

    <slot />
  </UDashboardGroup>
</template>
