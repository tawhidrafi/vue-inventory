import http from './http'

export const productSuppliersApi = {
  list: (productId) => http.get(`/products/${productId}/suppliers`),
  attach: (productId, data) => http.post(`/products/${productId}/suppliers`, data),
  update: (productId, supplierId, data) =>
    http.put(`/products/${productId}/suppliers/${supplierId}`, data),
  remove: (productId, supplierId) => http.delete(`/products/${productId}/suppliers/${supplierId}`),
}
