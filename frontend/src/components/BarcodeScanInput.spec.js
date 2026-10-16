import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import BarcodeScanInput from '@/components/BarcodeScanInput.vue'

describe('BarcodeScanInput', () => {
  it('emits the scanned code and clears itself on Enter', async () => {
    const wrapper = mount(BarcodeScanInput)
    const input = wrapper.get('input')

    await input.setValue('5901234123457')
    await input.trigger('keydown.enter')

    expect(wrapper.emitted('scan')).toEqual([['5901234123457']])
    expect(input.element.value).toBe('')
  })

  it('ignores blank input', async () => {
    const wrapper = mount(BarcodeScanInput)
    const input = wrapper.get('input')

    await input.setValue('   ')
    await input.trigger('keydown.enter')

    expect(wrapper.emitted('scan')).toBeUndefined()
  })
})
