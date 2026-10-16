import http from './http'

export const stocksApi = {
  list: (params) => http.get('/stocks', { params }),
  forProduct: (id) => http.get(`/products/${id}/stock`),
  lowStock: (params) => http.get('/reports/low-stock', { params }),
  summary: () => http.get('/reports/summary'),
  valuation: () => http.get('/reports/valuation'),
}
