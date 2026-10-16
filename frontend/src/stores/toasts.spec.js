import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useToastStore } from '@/stores/toasts'

describe('toasts store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('adds a success toast', () => {
    const toasts = useToastStore()

    toasts.success('Saved')

    expect(toasts.items).toHaveLength(1)
    expect(toasts.items[0]).toMatchObject({ message: 'Saved', type: 'success' })
  })

  it('adds error and info toasts with the right type', () => {
    const toasts = useToastStore()

    toasts.error('Boom')
    toasts.info('Heads up')

    expect(toasts.items.map((t) => t.type)).toEqual(['error', 'info'])
  })

  it('auto-dismisses a success toast after its timeout', () => {
    const toasts = useToastStore()

    toasts.success('Bye')
    expect(toasts.items).toHaveLength(1)

    vi.advanceTimersByTime(3500)

    expect(toasts.items).toHaveLength(0)
  })

  it('gives every toast a unique id', () => {
    const toasts = useToastStore()

    const first = toasts.success('one')
    const second = toasts.success('two')

    expect(first).not.toBe(second)
  })

  it('removes a toast by id', () => {
    const toasts = useToastStore()

    const id = toasts.push('manual', 'info', 0)

    toasts.remove(id)

    expect(toasts.items).toHaveLength(0)
  })
})
