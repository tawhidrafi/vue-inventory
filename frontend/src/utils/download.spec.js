import { afterEach, describe, expect, it, vi } from 'vitest'
import { downloadResponse } from '@/utils/download'

describe('downloadResponse', () => {
  afterEach(() => {
    vi.restoreAllMocks()
    vi.unstubAllGlobals()
  })

  it('creates an object URL, clicks a download link, then revokes it', () => {
    const createObjectURL = vi.fn(() => 'blob:fake')
    const revokeObjectURL = vi.fn()
    vi.stubGlobal('URL', { createObjectURL, revokeObjectURL })

    const link = document.createElement('a')
    const click = vi.spyOn(link, 'click').mockImplementation(() => {})
    vi.spyOn(document, 'createElement').mockReturnValue(link)

    downloadResponse(new Blob(['a,b']), 'export.csv')

    expect(createObjectURL).toHaveBeenCalledOnce()
    expect(link.download).toBe('export.csv')
    expect(click).toHaveBeenCalledOnce()
    expect(revokeObjectURL).toHaveBeenCalledWith('blob:fake')
  })

  it('wraps non-blob values in a blob', () => {
    const createObjectURL = vi.fn(() => 'blob:fake')
    vi.stubGlobal('URL', { createObjectURL, revokeObjectURL: vi.fn() })

    const link = document.createElement('a')
    vi.spyOn(link, 'click').mockImplementation(() => {})
    vi.spyOn(document, 'createElement').mockReturnValue(link)

    downloadResponse('raw,csv', 'raw.csv')

    expect(createObjectURL.mock.calls[0][0]).toBeInstanceOf(Blob)
  })
})
