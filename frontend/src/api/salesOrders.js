import http from './http'

export const salesOrdersApi = {
  list: (params) => http.get('/sales-orders', { params }),
  show: (id) => http.get(`/sales-orders/${id}`),
  create: (data) => http.post('/sales-orders', data),
  update: (id, data) => http.put(`/sales-orders/${id}`, data),
  remove: (id) => http.delete(`/sales-orders/${id}`),
  confirm: (id) => http.post(`/sales-orders/${id}/confirm`),
  ship: (id, data) => http.post(`/sales-orders/${id}/ship`, data),
  cancel: (id) => http.post(`/sales-orders/${id}/cancel`),
}
