import http from './http'

export const customersApi = {
  list: (params) => http.get('/customers', { params }),
  all: () => http.get('/customers', { params: { all: 1 } }),
  show: (id) => http.get(`/customers/${id}`),
  create: (data) => http.post('/customers', data),
  update: (id, data) => http.put(`/customers/${id}`, data),
  remove: (id) => http.delete(`/customers/${id}`),
}
