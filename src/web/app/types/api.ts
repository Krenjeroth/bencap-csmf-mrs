/**
 * Shapes returned by the CSMF-MRS API (src/api/app/Http/Resources).
 * Keep in step with those resources and docs/api.
 */

export interface RoleSummary {
  id: number
  title: string
  is_system: boolean
}

export interface User {
  id: string
  name: string
  email: string
  is_active: boolean
  must_change_password: boolean
  two_factor_enabled: boolean
  last_login_at: string | null
  roles?: RoleSummary[]
  created_at: string | null
  updated_at: string | null
}

/** GET /api/v1/me */
export interface Me extends User {
  roles: RoleSummary[]
  is_system_administrator: boolean
  two_factor_required: boolean
  permissions: string[]
}

export interface Permission {
  id: number
  title: string
  resource: string
  description: string | null
  is_protected: boolean
  roles_count?: number
  created_at: string | null
  updated_at: string | null
}

/** GET /api/v1/admin/permission-options */
export interface PermissionOption {
  id: number
  title: string
  resource: string
  description: string | null
}

export interface Role {
  id: number
  title: string
  description: string | null
  is_system: boolean
  permissions_count?: number
  users_count?: number
  permissions?: Permission[]
  created_at: string | null
  updated_at: string | null
}

/** Laravel resource collection with length-aware pagination. */
export interface Paginated<T> {
  data: T[]
  links: { first: string | null, last: string | null, prev: string | null, next: string | null }
  meta: {
    current_page: number
    from: number | null
    last_page: number
    per_page: number
    to: number | null
    total: number
  }
}

export interface Resource<T> {
  data: T
}

/** POST /api/v1/admin/users */
export interface CreatedUser extends Resource<User> {
  temporary_password: string
}
