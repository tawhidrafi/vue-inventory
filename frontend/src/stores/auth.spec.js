import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/http', () => ({
  default: { post: vi.fn(), get: vi.fn() },
}))

import http from '@/api/http'
import { useAuthStore } from '@/stores/auth'

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('stores the token and user on login', async () => {
    http.post.mockResolvedValue({
      data: { token: 'abc123', user: { name: 'Ada', role: 'admin' } },
    })

    const auth = useAuthStore()
    await auth.login('ada@example.com', 'secret')

    expect(auth.token).toBe('abc123')
    expect(auth.user).toEqual({ name: 'Ada', role: 'admin' })
    expect(localStorage.getItem('token')).toBe('abc123')
    expect(auth.isLoggedIn).toBe(true)
  })

  it('maps roles to the admin and canManage flags', async () => {
    http.post.mockResolvedValue({ data: { token: 't', user: { role: 'manager' } } })

    const auth = useAuthStore()
    await auth.login('x@example.com', 'secret')

    expect(auth.role).toBe('manager')
    expect(auth.canManage).toBe(true)
    expect(auth.isAdmin).toBe(false)
  })

  it('treats staff as non-managing', async () => {
    http.post.mockResolvedValue({ data: { token: 't', user: { role: 'staff' } } })

    const auth = useAuthStore()
    await auth.login('x@example.com', 'secret')

    expect(auth.canManage).toBe(false)
    expect(auth.isAdmin).toBe(false)
  })

  it('loads the current user with fetchUser', async () => {
    http.get.mockResolvedValue({ data: { name: 'Grace', role: 'staff' } })

    const auth = useAuthStore()
    await auth.fetchUser()

    expect(auth.user.name).toBe('Grace')
  })

  it('clears local state on logout even if the request fails', async () => {
    http.post.mockRejectedValue(new Error('offline'))

    const auth = useAuthStore()
    auth.token = 'x'
    auth.user = { role: 'admin' }
    localStorage.setItem('token', 'x')

    await auth.logout()

    expect(auth.token).toBeNull()
    expect(auth.user).toBeNull()
    expect(localStorage.getItem('token')).toBeNull()
  })
})
