import { z } from 'zod'

/** Mirrors Password::defaults() in the API (AppServiceProvider). */
export const PASSWORD_RULES = [
  { label: 'At least 12 characters', test: (v: string) => v.length >= 12 },
  { label: 'An upper case letter', test: (v: string) => /[A-Z]/.test(v) },
  { label: 'A lower case letter', test: (v: string) => /[a-z]/.test(v) },
  { label: 'A number', test: (v: string) => /\d/.test(v) },
  { label: 'A symbol', test: (v: string) => /[^A-Za-z0-9]/.test(v) },
] as const

export const passwordSchema = z.string().refine(
  value => PASSWORD_RULES.every(rule => rule.test(value)),
  'Use at least 12 characters with upper and lower case letters, a number and a symbol.',
)
