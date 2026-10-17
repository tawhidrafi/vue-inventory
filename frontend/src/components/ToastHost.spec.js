import { beforeEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ToastHost from '@/components/ToastHost.vue'
import { useToastStore } from '@/stores/toasts'

describe('ToastHost', () => {
  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
  })

  it('renders nothing when there are no toasts', () => {
    const wrapper = mount(ToastHost, { global: { plugins: [pinia] } })

    expect(wrapper.text()).toBe('')
  })

  it('renders a toast and dismisses it when the close button is clicked', async () => {
    const wrapper = mount(ToastHost, { global: { plugins: [pinia] } })

    useToastStore().success('Saved successfully')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Saved successfully')

    await wrapper.get('button').trigger('click')

    expect(wrapper.text()).not.toContain('Saved successfully')
  })

  it('is a polite live region and marks errors as alerts', async () => {
    const wrapper = mount(ToastHost, { global: { plugins: [pinia] } })

    expect(wrapper.attributes('aria-live')).toBe('polite')

    useToastStore().error('Boom')
    await wrapper.vm.$nextTick()

    expect(wrapper.get('[role="alert"]').text()).toContain('Boom')
    expect(wrapper.get('button').attributes('aria-label')).toBe('Dismiss notification')
  })
})
