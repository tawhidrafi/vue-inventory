import http from './http'

export const suppliersApi = {
  list: (params) => http.get('/suppliers', { params }),
  all: () => http.get('/suppliers', { params: { all: 1 } }),
  products: (id) => http.get(`/suppliers/${id}/products`),
  create: (data) => http.post('/suppliers', data),
  update: (id, data) => http.put(`/suppliers/${id}`, data),
  remove: (id) => http.delete(`/suppliers/${id}`),
}
