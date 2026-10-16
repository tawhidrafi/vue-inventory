import http from './http'

export const purchaseOrdersApi = {
  list: (params) => http.get('/purchase-orders', { params }),
  show: (id) => http.get(`/purchase-orders/${id}`),
  create: (data) => http.post('/purchase-orders', data),
  update: (id, data) => http.put(`/purchase-orders/${id}`, data),
  remove: (id) => http.delete(`/purchase-orders/${id}`),
  receive: (id, data) => http.post(`/purchase-orders/${id}/receive`, data),
  cancel: (id) => http.post(`/purchase-orders/${id}/cancel`),
}
