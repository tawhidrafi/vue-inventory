<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { warehousesApi } from '@/api/warehouses'
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
const form = reactive({ name: '', address: '', is_active: true })
const errors = ref({})
const saving = ref(false)

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await warehousesApi.list({
      page: page.value,
      search: search.value || undefined,
    })
    items.value = data.data
    meta.value = data.meta
  } catch {
    pageError.value = 'Could not load warehouses.'
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
  Object.assign(form, { name: '', address: '', is_active: true })
  errors.value = {}
  showForm.value = true
}

function openEdit(w) {
  editingId.value = w.id
  Object.assign(form, {
    name: w.name,
    address: w.address ?? '',
    is_active: w.is_active,
  })
  errors.value = {}
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}

  // send an empty address as null so the database stays clean
  const payload = {
    name: form.name,
    address: form.address || null,
    is_active: form.is_active,
  }

  try {
    if (editingId.value) {
      await warehousesApi.update(editingId.value, payload)
    } else {
      await warehousesApi.create(payload)
    }
    showForm.value = false
    toast.success(editingId.value ? 'Warehouse updated.' : 'Warehouse created.')
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

async function toggleActive(w) {
  try {
    await warehousesApi.update(w.id, {
      name: w.name,
      address: w.address ?? null,
      is_active: !w.is_active,
    })
    await load()
    toast.success(w.is_active ? 'Warehouse deactivated.' : 'Warehouse activated.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Update failed.')
  }
}

async function remove(w) {
  if (!confirm(`Delete "${w.name}"?`)) return
  try {
    await warehousesApi.remove(w.id)
    if (items.value.length === 1 && page.value > 1) page.value--
    await load()
    toast.success('Warehouse deleted.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Delete failed.')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-xl font-semibold">Warehouses</h2>
      <button
        v-if="auth.canManage"
        @click="openCreate"
        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
      >
        New warehouse
      </button>
    </div>

    <input
      v-model="search"
      type="text"
      aria-label="Search warehouses by name or address"
      placeholder="Search by name or address..."
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
            <th scope="col" class="px-4 py-3">Address</th>
            <th scope="col" class="px-4 py-3">Status</th>
            <th scope="col" class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No warehouses found.</td>
          </tr>
          <tr v-else v-for="w in items" :key="w.id" class="border-b last:border-0">
            <td class="px-4 py-3 font-medium">{{ w.name }}</td>
            <td class="px-4 py-3 text-gray-600">{{ w.address ?? '-' }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="w.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
              >
                {{ w.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <template v-if="auth.canManage">
                <button @click="toggleActive(w)" class="mr-3 text-gray-600 hover:underline">
                  {{ w.is_active ? 'Deactivate' : 'Activate' }}
                </button>
                <button @click="openEdit(w)" class="mr-3 text-blue-600 hover:underline">
                  Edit
                </button>
                <button @click="remove(w)" class="text-red-600 hover:underline">Delete</button>
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
      aria-label="Warehouse form"
      class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-4"
    >
      <form
        @submit.prevent="save"
        class="w-full max-w-md space-y-4 rounded-lg bg-white p-6 shadow-lg"
      >
        <h3 class="text-lg font-semibold">{{ editingId ? 'Edit warehouse' : 'New warehouse' }}</h3>

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
          <label class="mb-1 block text-sm font-medium">Address</label>
          <textarea
            v-model="form.address"
            rows="2"
            class="w-full rounded border border-gray-300 px-3 py-2"
          ></textarea>
          <p v-if="errors.address" class="mt-1 text-sm text-red-600">{{ errors.address[0] }}</p>
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
          Active
        </label>

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
