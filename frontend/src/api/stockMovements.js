import http from './http'

export const stockMovementsApi = {
  list: (params) => http.get('/stock-movements', { params }),
  create: (data) => http.post('/stock-movements', data),
  transfer: (data) => http.post('/stock-transfers', data),
  exportCsv: (params) => http.get('/exports/stock-movements', { params, responseType: 'blob' }),
}
