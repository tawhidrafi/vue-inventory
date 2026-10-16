import http from './http'

export const warehousesApi = {
  list: (params) => http.get('/warehouses', { params }),
  all: () => http.get('/warehouses', { params: { all: 1 } }),
  create: (data) => http.post('/warehouses', data),
  update: (id, data) => http.put(`/warehouses/${id}`, data),
  remove: (id) => http.delete(`/warehouses/${id}`),
}
