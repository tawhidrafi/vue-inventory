import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import http from '@/api/http'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token'))
  const user = ref(null)
  const isLoggedIn = computed(() => !!token.value)

  async function login(email, password) {
    const { data } = await http.post('/login', { email, password })
    token.value = data.token
    user.value = data.user
    localStorage.setItem('token', data.token)
  }

  async function fetchUser() {
    const { data } = await http.get('/me')
    user.value = data
  }

  async function logout() {
    try {
      await http.post('/logout')
    } finally {
      clear()
    }
  }

  function clear() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  return { token, user, isLoggedIn, login, fetchUser, logout, clear }
})