<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { usersApi } from '@/api/users'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toasts'

const auth = useAuthStore()
const toast = useToastStore()

const items = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const loading = ref(false)
const pageError = ref('')

const roleStyles = {
  admin: 'bg-purple-100 text-purple-700',
  manager: 'bg-blue-100 text-blue-700',
  staff: 'bg-gray-200 text-gray-700',
}

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', email: '', password: '', role: 'staff' })
const errors = ref({})
const saving = ref(false)

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await usersApi.list({
      page: page.value,
      search: search.value || undefined,
    })
    items.value = data.data
    meta.value = data.meta
  } catch {
    pageError.value = 'Could not load users.'
  } finally {
    loading.value = false
  }
}

let timer
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    page.value = 1
    load()
  }, 300)
})

function go(p) {
  page.value = p
  load()
}

function openCreate() {
  editingId.value = null
  Object.assign(form, { name: '', email: '', password: '', role: 'staff' })
  errors.value = {}
  showForm.value = true
}

function openEdit(user) {
  editingId.value = user.id
  Object.assign(form, { name: user.name, email: user.email, password: '', role: user.role })
  errors.value = {}
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: form.name,
    email: form.email,
    role: form.role,
  }
  if (form.password) payload.password = form.password

  try {
    if (editingId.value) {
      await usersApi.update(editingId.value, payload)
    } else {
      await usersApi.create(payload)
    }
    showForm.value = false
    toast.success(editingId.value ? 'User updated.' : 'User created.')
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    } else {
      errors.value = { name: ['Something went wrong. Please try again.'] }
    }
  } finally {
    saving.value = false
  }
}

async function remove(user) {
  if (!confirm(`Delete "${user.name}"?`)) return
  try {
    await usersApi.remove(user.id)
    if (items.value.length === 1 && page.value > 1) page.value--
    await load()
    toast.success('User deleted.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Delete failed.')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-xl font-semibold">Users</h2>
      <button
        @click="openCreate"
        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
      >
        New user
      </button>
    </div>

    <input
      v-model="search"
      type="text"
      aria-label="Search users by name or email"
      placeholder="Search by name or email..."
      class="mb-4 w-full max-w-xs rounded border border-gray-300 bg-white px-3 py-2 text-sm"
    />

    <p v-if="pageError" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700">
      {{ pageError }}
    </p>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
      <table class="w-full text-left text-sm">
        <thead class="border-b bg-gray-50 text-gray-600">
          <tr>
            <th scope="col" class="px-4 py-3">Name</th>
            <th scope="col" class="px-4 py-3">Email</th>
            <th scope="col" class="px-4 py-3">Role</th>
            <th scope="col" class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No users found.</td>
          </tr>
          <tr v-else v-for="u in items" :key="u.id" class="border-b last:border-0">
            <td class="px-4 py-3 font-medium">
              {{ u.name }}
              <span v-if="u.id === auth.user?.id" class="ml-1 text-xs text-gray-400">(you)</span>
            </td>
            <td class="px-4 py-3 text-gray-600">{{ u.email }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                :class="roleStyles[u.role]"
              >
                {{ u.role }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button @click="openEdit(u)" class="mr-3 text-blue-600 hover:underline">Edit</button>
              <button
                @click="remove(u)"
                :disabled="u.id === auth.user?.id"
                class="text-red-600 hover:underline disabled:text-gray-300 disabled:no-underline"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="meta && meta.last_page > 1" class="mt-4 flex items-center justify-between text-sm">
      <span class="text-gray-600"
        >Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)</span
      >
      <div class="flex gap-2">
        <button
          :disabled="meta.current_page <= 1"
          @click="go(meta.current_page - 1)"
          class="rounded border bg-white px-3 py-1 disabled:opacity-50"
        >
          Previous
        </button>
        <button
          :disabled="meta.current_page >= meta.last_page"
          @click="go(meta.current_page + 1)"
          class="rounded border bg-white px-3 py-1 disabled:opacity-50"
        >
          Next
        </button>
      </div>
    </div>

    <!-- create / edit modal -->
    <div
      v-if="showForm"
      role="dialog"
      aria-modal="true"
      aria-label="User form"
      class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-4"
    >
      <form
        @submit.prevent="save"
        class="w-full max-w-md space-y-4 rounded-lg bg-white p-6 shadow-lg"
      >
        <h3 class="text-lg font-semibold">{{ editingId ? 'Edit user' : 'New user' }}</h3>

        <div>
          <label class="mb-1 block text-sm font-medium">Name</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded border border-gray-300 px-3 py-2"
          />
          <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full rounded border border-gray-300 px-3 py-2"
          />
          <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">
            Password
            <span v-if="editingId" class="text-gray-400">(leave blank to keep current)</span>
          </label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            class="w-full rounded border border-gray-300 px-3 py-2"
          />
          <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password[0] }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Role</label>
          <select
            v-model="form.role"
            class="w-full rounded border border-gray-300 bg-white px-3 py-2"
          >
            <option value="staff">Staff — daily operations only</option>
            <option value="manager">Manager — all inventory operations</option>
            <option value="admin">Admin — everything, including users</option>
          </select>
          <p v-if="errors.role" class="mt-1 text-sm text-red-600">{{ errors.role[0] }}</p>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" @click="showForm = false" class="rounded border px-4 py-2 text-sm">
            Cancel
          </button>
          <button
            :disabled="saving"
            class="rounded bg-blue-600 px-4 py-2 text-sm text-white disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
