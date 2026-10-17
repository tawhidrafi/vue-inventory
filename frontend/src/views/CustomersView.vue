<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { customersApi } from '@/api/customers'
import { useToastStore } from '@/stores/toasts'
import { useAuthStore } from '@/stores/auth'

const toast = useToastStore()
const auth = useAuthStore()

const items = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const activeFilter = ref('')
const loading = ref(false)
const pageError = ref('')

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({
  name: '',
  company_name: '',
  email: '',
  phone: '',
  address: '',
  tax_number: '',
  is_active: true,
})
const errors = ref({})
const saving = ref(false)

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await customersApi.list({
      page: page.value,
      search: search.value || undefined,
      is_active: activeFilter.value === '' ? undefined : activeFilter.value,
    })
    items.value = data.data
    meta.value = data.meta
  } catch {
    pageError.value = 'Could not load customers.'
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

watch(activeFilter, () => {
  page.value = 1
  load()
})

function go(p) {
  page.value = p
  load()
}

function resetForm() {
  Object.assign(form, {
    name: '',
    company_name: '',
    email: '',
    phone: '',
    address: '',
    tax_number: '',
    is_active: true,
  })
  errors.value = {}
}

function openCreate() {
  editingId.value = null
  resetForm()
  showForm.value = true
}

function openEdit(c) {
  editingId.value = c.id
  resetForm()
  Object.assign(form, {
    name: c.name,
    company_name: c.company_name ?? '',
    email: c.email ?? '',
    phone: c.phone ?? '',
    address: c.address ?? '',
    tax_number: c.tax_number ?? '',
    is_active: c.is_active,
  })
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: form.name,
    company_name: form.company_name || null,
    email: form.email || null,
    phone: form.phone || null,
    address: form.address || null,
    tax_number: form.tax_number || null,
    is_active: form.is_active,
  }

  try {
    if (editingId.value) {
      await customersApi.update(editingId.value, payload)
    } else {
      await customersApi.create(payload)
    }
    showForm.value = false
    toast.success(editingId.value ? 'Customer updated.' : 'Customer created.')
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

async function remove(c) {
  if (!confirm(`Delete "${c.name}"?`)) return
  try {
    await customersApi.remove(c.id)
    if (items.value.length === 1 && page.value > 1) page.value--
    await load()
    toast.success('Customer deleted.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Delete failed.')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-xl font-semibold">Customers</h2>
      <button
        v-if="auth.canManage"
        @click="openCreate"
        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
      >
        New customer
      </button>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <input
        v-model="search"
        type="text"
        aria-label="Search customers"
        placeholder="Search by name, company, email or phone..."
        class="w-full max-w-xs rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      />
      <select
        v-model="activeFilter"
        aria-label="Filter by status"
        class="rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      >
        <option value="">All statuses</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
    </div>

    <p v-if="pageError" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700">
      {{ pageError }}
    </p>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
      <table class="w-full text-left text-sm">
        <thead class="border-b bg-gray-50 text-gray-600">
          <tr>
            <th scope="col" class="px-4 py-3">Customer</th>
            <th scope="col" class="px-4 py-3">Company</th>
            <th scope="col" class="px-4 py-3">Email</th>
            <th scope="col" class="px-4 py-3">Phone</th>
            <th scope="col" class="px-4 py-3">Orders</th>
            <th scope="col" class="px-4 py-3">Status</th>
            <th scope="col" class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="7" class="px-4 py-6 text-center text-gray-500">No customers found.</td>
          </tr>
          <tr v-else v-for="c in items" :key="c.id" class="border-b last:border-0">
            <td class="px-4 py-3 font-medium">{{ c.name }}</td>
            <td class="px-4 py-3 text-gray-600">{{ c.company_name ?? '-' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ c.email ?? '-' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ c.phone ?? '-' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ c.sales_orders_count ?? 0 }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="c.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
              >
                {{ c.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <template v-if="auth.canManage">
                <button @click="openEdit(c)" class="mr-3 text-blue-600 hover:underline">
                  Edit
                </button>
                <button @click="remove(c)" class="text-red-600 hover:underline">Delete</button>
              </template>
              <span v-else class="text-gray-400">-</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="meta && meta.last_page > 1" class="mt-4 flex items-center justify-between text-sm">
      <span class="text-gray-600">
        Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)
      </span>
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

    <!-- customer form modal -->
    <div
      v-if="showForm"
      role="dialog"
      aria-modal="true"
      aria-label="Customer form"
      class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-4"
    >
      <form
        @submit.prevent="save"
        class="w-full max-w-lg space-y-4 rounded-lg bg-white p-6 shadow-lg"
      >
        <h3 class="text-lg font-semibold">{{ editingId ? 'Edit customer' : 'New customer' }}</h3>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1 block text-sm font-medium">Full Name</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">Company Name</label>
            <input
              v-model="form.company_name"
              type="text"
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
            />
            <p v-if="errors.company_name" class="mt-1 text-sm text-red-600">
              {{ errors.company_name[0] }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1 block text-sm font-medium">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">Phone</label>
            <input
              v-model="form.phone"
              type="text"
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
            />
            <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone[0] }}</p>
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Tax / VAT ID</label>
          <input
            v-model="form.tax_number"
            type="text"
            class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
          />
          <p v-if="errors.tax_number" class="mt-1 text-sm text-red-600">
            {{ errors.tax_number[0] }}
          </p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Address</label>
          <textarea
            v-model="form.address"
            rows="2"
            class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
          ></textarea>
          <p v-if="errors.address" class="mt-1 text-sm text-red-600">{{ errors.address[0] }}</p>
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
          Active Customer
        </label>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="showForm = false" class="rounded border px-4 py-2 text-sm">
            Cancel
          </button>
          <button
            :disabled="saving"
            class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
