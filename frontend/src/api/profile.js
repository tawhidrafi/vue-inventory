import http from './http'

export const profileApi = {
  updatePreferences: (data) => http.put('/me/preferences', data),
}
