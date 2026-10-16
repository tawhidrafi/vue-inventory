import http from './http'

export const activityLogsApi = {
  list: (params) => http.get('/activity-logs', { params }),
}
