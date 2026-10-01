<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'

defineProps<{ collapsed?: boolean }>()

const { user } = useCurrentUser()
const { signOut } = useSignIn()
const colorMode = useColorMode()

const roleLabel = computed(() => (user.value?.roles ?? []).map(role => role.title).join(', ') || 'No role')

const items = computed<DropdownMenuItem[][]>(() => [
  [{ label: user.value?.email ?? '', type: 'label' }],
  [
    { label: 'Change password', icon: 'i-lucide-key-round', to: '/account/password' },
    { label: 'Two-factor login', icon: 'i-lucide-shield-check', to: '/account/security' },
  ],
  [
    {
      label: 'Appearance',
      icon: 'i-lucide-sun-moon',
      children: [
        { label: 'Light', icon: 'i-lucide-sun', type: 'checkbox', checked: colorMode.preference === 'light', onSelect: () => (colorMode.preference = 'light') },
        { label: 'Dark', icon: 'i-lucide-moon', type: 'checkbox', checked: colorMode.preference === 'dark', onSelect: () => (colorMode.preference = 'dark') },
        { label: 'Match system', icon: 'i-lucide-monitor', type: 'checkbox', checked: colorMode.preference === 'system', onSelect: () => (colorMode.preference = 'system') },
      ],
    },
  ],
  [{ label: 'Sign out', icon: 'i-lucide-log-out', onSelect: () => signOut() }],
])
</script>

<template>
  <UDropdownMenu
    :items="items"
    :content="{ align: 'center', collisionPadding: 12 }"
    :ui="{ content: collapsed ? 'w-48' : 'w-(--reka-dropdown-menu-trigger-width)' }"
  >
    <UButton
      :label="collapsed ? undefined : user?.name"
      :avatar="{ alt: user?.name ?? 'User' }"
      :trailing-icon="collapsed ? undefined : 'i-lucide-chevrons-up-down'"
      color="neutral"
      variant="ghost"
      block
      :square="collapsed"
      class="data-[state=open]:bg-elevated"
      :ui="{ trailingIcon: 'text-dimmed' }"
    >
      <template
        v-if="!collapsed"
        #default
      >
        <span class="flex min-w-0 flex-col text-left">
          <span class="truncate text-sm font-medium text-highlighted">{{ user?.name }}</span>
          <span class="truncate text-xs text-muted">{{ roleLabel }}</span>
        </span>
      </template>
    </UButton>
  </UDropdownMenu>
</template>
