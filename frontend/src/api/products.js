import http from './http'

// Laravel can't read multipart bodies on PUT, so updates use method spoofing.
function toFormData(data) {
  const fd = new FormData()

  Object.entries(data).forEach(([key, value]) => {
    if (value === undefined || value === null) return

    if (key === 'is_active') {
      fd.append(key, value ? '1' : '0')
      return
    }

    fd.append(key, value)
  })

  return fd
}

export const productsApi = {
  list: (params) => http.get('/products', { params }),
  all: () => http.get('/products', { params: { all: 1 } }),
  lookup: (code) => http.get('/products/lookup', { params: { code } }),
  create: (data) => http.post('/products', toFormData(data)),
  update: (id, data) => {
    const fd = toFormData(data)
    fd.append('_method', 'PUT')
    return http.post(`/products/${id}`, fd)
  },
  remove: (id) => http.delete(`/products/${id}`),
  restore: (id) => http.post(`/products/${id}/restore`),
  forceDelete: (id) => http.delete(`/products/${id}/force`),
  exportCsv: () => http.get('/exports/products', { responseType: 'blob' }),
}
