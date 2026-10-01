import { describe, expect, it } from 'vitest'
import { PASSWORD_STEP, TWO_FACTOR_STEP, pendingAccountStep, safeRedirect } from '~/utils/accountSteps'

const done = { must_change_password: false, two_factor_required: false, two_factor_enabled: false }

describe('pendingAccountStep', () => {
  it('has nothing pending for a signed-out visitor', () => {
    expect(pendingAccountStep(null)).toBeNull()
  })

  it('has nothing pending for a fully set-up account', () => {
    expect(pendingAccountStep(done)).toBeNull()
    expect(pendingAccountStep({ ...done, two_factor_required: true, two_factor_enabled: true })).toBeNull()
  })

  it('asks for a new password first', () => {
    expect(pendingAccountStep({ ...done, must_change_password: true })).toBe(PASSWORD_STEP)
    expect(pendingAccountStep({ must_change_password: true, two_factor_required: true, two_factor_enabled: false })).toBe(PASSWORD_STEP)
  })

  it('then asks a System Administrator to turn on two-factor', () => {
    expect(pendingAccountStep({ ...done, two_factor_required: true })).toBe(TWO_FACTOR_STEP)
  })

  it('does not require two-factor for other roles', () => {
    expect(pendingAccountStep({ ...done, two_factor_required: false, two_factor_enabled: false })).toBeNull()
  })
})

describe('safeRedirect', () => {
  it('keeps same-app paths', () => {
    expect(safeRedirect('/admin/users?page=2')).toBe('/admin/users?page=2')
  })

  it.each([
    ['https://evil.example/phish'],
    ['//evil.example'],
    ['/\\evil.example'],
    ['javascript:alert(1)'],
    [''],
    [undefined],
    [['/admin']],
  ])('falls back to home for %j', (target) => {
    expect(safeRedirect(target)).toBe('/')
  })
})
