<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { suppliersApi } from '@/api/suppliers'
import { useToastStore } from '@/stores/toasts'
import { useAuthStore } from '@/stores/auth'

const toast = useToastStore()
const auth = useAuthStore()

const items = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const loading = ref(false)
const pageError = ref('')

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', email: '', phone: '', address: '', notes: '' })
const errors = ref({})
const saving = ref(false)

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await suppliersApi.list({
      page: page.value,
      search: search.value || undefined,
    })
    items.value = data.data
    meta.value = data.meta
  } catch {
    pageError.value = 'Could not load suppliers.'
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
  Object.assign(form, { name: '', email: '', phone: '', address: '', notes: '' })
  errors.value = {}
  showForm.value = true
}

function openEdit(s) {
  editingId.value = s.id
  Object.assign(form, {
    name: s.name,
    email: s.email ?? '',
    phone: s.phone ?? '',
    address: s.address ?? '',
    notes: s.notes ?? '',
  })
  errors.value = {}
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}

  // send empty strings as null so the database stays clean
  const payload = Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v]))

  try {
    if (editingId.value) {
      await suppliersApi.update(editingId.value, payload)
    } else {
      await suppliersApi.create(payload)
    }
    showForm.value = false
    toast.success(editingId.value ? 'Supplier updated.' : 'Supplier created.')
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

async function remove(s) {
  if (!confirm(`Delete "${s.name}"?`)) return
  try {
    await suppliersApi.remove(s.id)
    if (items.value.length === 1 && page.value > 1) page.value--
    await load()
    toast.success('Supplier deleted.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Delete failed.')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-xl font-semibold">Suppliers</h2>
      <button
        v-if="auth.canManage"
        @click="openCreate"
        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
      >
        New supplier
      </button>
    </div>

    <input
      v-model="search"
      type="text"
      aria-label="Search suppliers by name, email or phone"
      placeholder="Search by name, email or phone..."
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
            <th scope="col" class="px-4 py-3">Phone</th>
            <th scope="col" class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No suppliers found.</td>
          </tr>
          <tr v-else v-for="s in items" :key="s.id" class="border-b last:border-0">
            <td class="px-4 py-3 font-medium">{{ s.name }}</td>
            <td class="px-4 py-3 text-gray-600">{{ s.email ?? '-' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ s.phone ?? '-' }}</td>
            <td class="px-4 py-3 text-right">
              <template v-if="auth.canManage">
                <button @click="openEdit(s)" class="mr-3 text-blue-600 hover:underline">
                  Edit
                </button>
                <button @click="remove(s)" class="text-red-600 hover:underline">Delete</button>
              </template>
              <span v-else class="text-gray-400">-</span>
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

    <div
      v-if="showForm"
      role="dialog"
      aria-modal="true"
      aria-label="Supplier form"
      class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-4"
    >
      <form
        @submit.prevent="save"
        class="w-full max-w-md space-y-4 rounded-lg bg-white p-6 shadow-lg"
      >
        <h3 class="text-lg font-semibold">{{ editingId ? 'Edit supplier' : 'New supplier' }}</h3>

        <div>
          <label class="mb-1 block text-sm font-medium">Name</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded border border-gray-300 px-3 py-2"
          />
          <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
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
            <label class="mb-1 block text-sm font-medium">Phone</label>
            <input
              v-model="form.phone"
              type="text"
              class="w-full rounded border border-gray-300 px-3 py-2"
            />
            <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone[0] }}</p>
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Address</label>
          <textarea
            v-model="form.address"
            rows="2"
            class="w-full rounded border border-gray-300 px-3 py-2"
          ></textarea>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Notes</label>
          <textarea
            v-model="form.notes"
            rows="2"
            class="w-full rounded border border-gray-300 px-3 py-2"
          ></textarea>
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
