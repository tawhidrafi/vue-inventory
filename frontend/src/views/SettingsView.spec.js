import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/profile', () => ({
  profileApi: { updatePreferences: vi.fn() },
}))

import { profileApi } from '@/api/profile'
import SettingsView from '@/views/SettingsView.vue'
import { useAuthStore } from '@/stores/auth'

describe('SettingsView', () => {
  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('shows the saved notification preferences', async () => {
    const auth = useAuthStore()
    auth.applyUser({
      name: 'Ada',
      email: 'ada@example.com',
      role: 'admin',
      notify_low_stock: true,
      notify_purchase_orders: false,
    })

    const wrapper = mount(SettingsView, { global: { plugins: [pinia] } })
    await flushPromises()

    const boxes = wrapper.findAll('input[type="checkbox"]')
    expect(boxes[0].element.checked).toBe(true)
    expect(boxes[1].element.checked).toBe(false)
  })

  it('saves the preferences and refreshes the signed-in user', async () => {
    const auth = useAuthStore()
    auth.applyUser({
      name: 'Ada',
      role: 'staff',
      notify_low_stock: false,
      notify_purchase_orders: false,
    })

    profileApi.updatePreferences.mockResolvedValue({
      data: {
        name: 'Ada',
        role: 'staff',
        notify_low_stock: true,
        notify_purchase_orders: false,
      },
    })

    const wrapper = mount(SettingsView, { global: { plugins: [pinia] } })
    await flushPromises()

    await wrapper.findAll('input[type="checkbox"]')[0].setValue(true)
    await wrapper.get('button').trigger('click')
    await flushPromises()

    expect(profileApi.updatePreferences).toHaveBeenCalledWith({
      notify_low_stock: true,
      notify_purchase_orders: false,
    })
    expect(auth.user.notify_low_stock).toBe(true)
  })
})
