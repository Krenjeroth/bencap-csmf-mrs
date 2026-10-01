import { describe, expect, it } from 'vitest'
import { parseApiError } from '~/utils/apiError'

describe('parseApiError', () => {
  it('turns Laravel validation errors into field messages', () => {
    const info = parseApiError({
      statusCode: 422,
      data: { message: 'The email has already been taken. (and 1 more error)', errors: { 'email': ['The email has already been taken.'], 'role_ids.0': ['The selected role is invalid.'] } },
    })

    expect(info.status).toBe(422)
    expect(info.fields).toEqual({ 'email': 'The email has already been taken.', 'role_ids.0': 'The selected role is invalid.' })
    expect(info.message).toBe('The email has already been taken.')
  })

  it('keeps the API message and code for refusals', () => {
    const info = parseApiError({ statusCode: 403, data: { message: 'Turn on two-factor login to continue.', code: 'two_factor_required' } })

    expect(info.message).toBe('Turn on two-factor login to continue.')
    expect(info.code).toBe('two_factor_required')
  })

  it.each([
    [401, 'Your session has ended. Sign in again.'],
    [419, 'The page expired. Reload and try again.'],
    [429, 'Too many attempts. Wait a minute and try again.'],
  ])('explains a bare %i', (status, message) => {
    expect(parseApiError({ statusCode: status }).message).toBe(message)
  })

  it('hides server error details', () => {
    const info = parseApiError({ statusCode: 500, data: { message: 'SQLSTATE[42S02]: Base table not found' } })

    expect(info.message).not.toContain('SQLSTATE')
  })

  it('explains a network failure', () => {
    expect(parseApiError(new TypeError('Failed to fetch')).message).toContain('Could not reach the server')
  })

  it('copes with nothing at all', () => {
    expect(parseApiError(undefined).status).toBeNull()
  })
})
