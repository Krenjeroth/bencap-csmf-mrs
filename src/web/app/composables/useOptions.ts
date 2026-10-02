import type { OfficeOption, RoleSummary } from '~/types/api'

/**
 * Lookup lists for pickers, loaded once per page visit and shared between
 * components. Each list needs its own permission on the API, so a failed
 * load leaves that list empty instead of breaking the page.
 */
export function useOptions() {
  const client = useSanctumClient()

  const offices = useState<OfficeOption[]>('options-offices', () => [])
  const serviceTypes = useState<{ id: number, type: string }[]>('options-service-types', () => [])
  const roles = useState<RoleSummary[]>('options-roles', () => [])

  async function load<T>(endpoint: string, target: Ref<T[]>) {
    try {
      target.value = (await client<{ data: T[] }>(endpoint)).data
    }
    catch {
      target.value = []
    }
  }

  return {
    offices,
    serviceTypes,
    roles,
    loadOffices: () => load('/api/v1/admin/office-options', offices),
    loadServiceTypes: () => load('/api/v1/admin/service-type-options', serviceTypes),
    loadRoles: () => load('/api/v1/admin/role-options', roles),
  }
}
