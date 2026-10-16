import http from './http'

export const categoriesApi = {
  list: (params) => http.get('/categories', { params }),
  all: () => http.get('/categories', { params: { all: 1 } }),
  create: (data) => http.post('/categories', data),
  update: (id, data) => http.put(`/categories/${id}`, data),
  remove: (id) => http.delete(`/categories/${id}`),
  restore: (id) => http.post(`/categories/${id}/restore`),
  forceDelete: (id) => http.delete(`/categories/${id}/force`),
}
