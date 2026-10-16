<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const message = ref('')
const errors = ref({})
const loading = ref(false)

async function submit() {
  loading.value = true
  message.value = ''
  errors.value = {}

  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    const status = e.response?.status
    if (status === 422) {
      message.value = e.response.data.message
      errors.value = e.response.data.errors ?? {}
    } else if (status === 429) {
      message.value = 'Too many attempts. Please wait a minute.'
    } else {
      message.value = 'Something went wrong. Is the server running?'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-100">
    <form @submit.prevent="submit" class="w-full max-w-sm space-y-4 rounded-lg bg-white p-6 shadow">
      <h1 class="text-xl font-semibold">Sign in</h1>

      <p v-if="message" class="rounded bg-red-50 px-3 py-2 text-sm text-red-700">{{ message }}</p>

      <div>
        <label class="mb-1 block text-sm font-medium">Email</label>
        <input
          v-model="email"
          type="email"
          class="w-full rounded border border-gray-300 px-3 py-2"
        />
        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium">Password</label>
        <input
          v-model="password"
          type="password"
          class="w-full rounded border border-gray-300 px-3 py-2"
        />
        <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password[0] }}</p>
      </div>

      <button
        :disabled="loading"
        class="w-full rounded bg-blue-600 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
      >
        {{ loading ? 'Signing in...' : 'Sign in' }}
      </button>
    </form>
  </div>
</template>
