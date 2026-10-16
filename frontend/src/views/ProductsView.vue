<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { productsApi } from '@/api/products'
import { categoriesApi } from '@/api/categories'

// image URLs are served by the backend origin, not the frontend one
const API_ORIGIN = (import.meta.env.VITE_API_URL || '').replace(/\/api\/?$/, '')
function imageUrl(path) {
  return path ? `${API_ORIGIN}/storage/${path}` : null
}

const items = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const categoryFilter = ref('')
const activeFilter = ref('')
const loading = ref(false)
const pageError = ref('')

// all categories, for the filter + form dropdowns
const categories = ref([])

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({
  name: '',
  sku: '',
  barcode: '',
  category_id: '',
  unit: 'pcs',
  cost_price: '',
  sell_price: '',
  reorder_level: '',
  description: '',
  is_active: true,
  image: null,
})
const imageFile = ref(null)
const imagePreview = ref(null)
const errors = ref({})
const saving = ref(false)

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await productsApi.list({
      page: page.value,
      search: search.value || undefined,
      category_id: categoryFilter.value || undefined,
      is_active: activeFilter.value === '' ? undefined : activeFilter.value,
    })
    items.value = data.data
    meta.value = data.meta
  } catch {
    pageError.value = 'Could not load products.'
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  const { data } = await categoriesApi.all()
  categories.value = data.data
}

let timer
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    page.value = 1
    load()
  }, 300)
})

watch([categoryFilter, activeFilter], () => {
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
    sku: '',
    barcode: '',
    category_id: '',
    unit: 'pcs',
    cost_price: '',
    sell_price: '',
    reorder_level: '',
    description: '',
    is_active: true,
    image: null,
  })
  imageFile.value = null
  imagePreview.value = null
  errors.value = {}
}

function openCreate() {
  editingId.value = null
  resetForm()
  showForm.value = true
}

function openEdit(p) {
  editingId.value = p.id
  resetForm()
  Object.assign(form, {
    name: p.name,
    sku: p.sku ?? '',
    barcode: p.barcode ?? '',
    category_id: p.category_id ?? '',
    unit: p.unit ?? 'pcs',
    cost_price: p.cost_price ?? '',
    sell_price: p.sell_price ?? '',
    reorder_level: p.reorder_level ?? '',
    description: p.description ?? '',
    is_active: p.is_active,
    image: p.image,
  })
  imagePreview.value = imageUrl(p.image)
  showForm.value = true
}

function onFileChange(e) {
  const file = e.target.files?.[0] ?? null
  imageFile.value = file
  if (file) imagePreview.value = URL.createObjectURL(file)
}

async function save() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: form.name,
    sku: form.sku || null,
    barcode: form.barcode || null,
    category_id: form.category_id || null,
    unit: form.unit || 'pcs',
    cost_price: form.cost_price === '' ? null : form.cost_price,
    sell_price: form.sell_price === '' ? null : form.sell_price,
    reorder_level: form.reorder_level === '' ? null : form.reorder_level,
    description: form.description || null,
    is_active: form.is_active,
  }
  if (imageFile.value) payload.image = imageFile.value

  try {
    if (editingId.value) {
      await productsApi.update(editingId.value, payload)
    } else {
      await productsApi.create(payload)
    }
    showForm.value = false
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

async function remove(p) {
  if (!confirm(`Delete "${p.name}"?`)) return
  try {
    await productsApi.remove(p.id)
    if (items.value.length === 1 && page.value > 1) page.value--
    await load()
  } catch (e) {
    alert(e.response?.data?.message ?? 'Delete failed.')
  }
}

onMounted(() => {
  load()
  loadCategories()
})
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-xl font-semibold">Products</h2>
      <button @click="openCreate" class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
        New product
      </button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
      <input
        v-model="search"
        type="text"
        placeholder="Search by name, SKU or barcode..."
        class="w-full max-w-xs rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      />
      <select v-model="categoryFilter" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm">
        <option value="">All categories</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <select v-model="activeFilter" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm">
        <option value="">All statuses</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
    </div>

    <p v-if="pageError" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700">{{ pageError }}</p>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
      <table class="w-full text-left text-sm">
        <thead class="border-b bg-gray-50 text-gray-600">
          <tr>
            <th class="px-4 py-3">Image</th>
            <th class="px-4 py-3">SKU</th>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Category</th>
            <th class="px-4 py-3">Cost</th>
            <th class="px-4 py-3">Sell</th>
            <th class="px-4 py-3">Stock</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="9" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="9" class="px-4 py-6 text-center text-gray-500">No products found.</td>
          </tr>
          <tr v-else v-for="p in items" :key="p.id" class="border-b last:border-0">
            <td class="px-4 py-2">
              <img
                v-if="p.image"
                :src="imageUrl(p.image)"
                alt=""
                class="h-10 w-10 rounded object-cover"
              />
              <span v-else class="text-gray-400">-</span>
            </td>
            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ p.sku }}</td>
            <td class="px-4 py-3 font-medium">{{ p.name }}</td>
            <td class="px-4 py-3 text-gray-600">{{ p.category?.name ?? '-' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ Number(p.cost_price).toFixed(2) }}</td>
            <td class="px-4 py-3 text-gray-600">{{ Number(p.sell_price).toFixed(2) }}</td>
            <td class="px-4 py-3">
              <span :class="p.stock_total <= p.reorder_level ? 'font-semibold text-red-600' : 'text-gray-600'">
                {{ p.stock_total }}
              </span>
              <span
                v-if="p.stock_total <= p.reorder_level"
                class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700"
              >
                Low
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
              >
                {{ p.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button @click="openEdit(p)" class="mr-3 text-blue-600 hover:underline">Edit</button>
              <button @click="remove(p)" class="text-red-600 hover:underline">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="meta && meta.last_page > 1" class="mt-4 flex items-center justify-between text-sm">
      <span class="text-gray-600">Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)</span>
      <div class="flex gap-2">
        <button :disabled="meta.current_page <= 1" @click="go(meta.current_page - 1)" class="rounded border bg-white px-3 py-1 disabled:opacity-50">Previous</button>
        <button :disabled="meta.current_page >= meta.last_page" @click="go(meta.current_page + 1)" class="rounded border bg-white px-3 py-1 disabled:opacity-50">Next</button>
      </div>
    </div>

    <div v-if="showForm" class="fixed inset-0 z-10 flex items-center justify-center overflow-y-auto bg-black/40 p-4">
      <form @submit.prevent="save" class="my-8 w-full max-w-2xl space-y-4 rounded-lg bg-white p-6 shadow-lg">
        <h3 class="text-lg font-semibold">{{ editingId ? 'Edit product' : 'New product' }}</h3>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1 block text-sm font-medium">Name</label>
            <input v-model="form.name" type="text" class="w-full rounded border border-gray-300 px-3 py-2" />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">Category</label>
            <select v-model="form.category_id" class="w-full rounded border border-gray-300 bg-white px-3 py-2">
              <option value="">None</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <p v-if="errors.category_id" class="mt-1 text-sm text-red-600">{{ errors.category_id[0] }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1 block text-sm font-medium">SKU</label>
            <input
              v-model="form.sku"
              type="text"
              placeholder="Leave blank to auto-generate"
              class="w-full rounded border border-gray-300 px-3 py-2"
            />
            <p v-if="errors.sku" class="mt-1 text-sm text-red-600">{{ errors.sku[0] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">Barcode</label>
            <input v-model="form.barcode" type="text" class="w-full rounded border border-gray-300 px-3 py-2" />
            <p v-if="errors.barcode" class="mt-1 text-sm text-red-600">{{ errors.barcode[0] }}</p>
          </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
          <div>
            <label class="mb-1 block text-sm font-medium">Unit</label>
            <input v-model="form.unit" type="text" class="w-full rounded border border-gray-300 px-3 py-2" />
            <p v-if="errors.unit" class="mt-1 text-sm text-red-600">{{ errors.unit[0] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">Cost</label>
            <input v-model="form.cost_price" type="number" step="0.01" min="0" class="w-full rounded border border-gray-300 px-3 py-2" />
            <p v-if="errors.cost_price" class="mt-1 text-sm text-red-600">{{ errors.cost_price[0] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">Sell</label>
            <input v-model="form.sell_price" type="number" step="0.01" min="0" class="w-full rounded border border-gray-300 px-3 py-2" />
            <p v-if="errors.sell_price" class="mt-1 text-sm text-red-600">{{ errors.sell_price[0] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">Reorder level</label>
            <input v-model="form.reorder_level" type="number" min="0" class="w-full rounded border border-gray-300 px-3 py-2" />
            <p v-if="errors.reorder_level" class="mt-1 text-sm text-red-600">{{ errors.reorder_level[0] }}</p>
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Description</label>
          <textarea v-model="form.description" rows="2" class="w-full rounded border border-gray-300 px-3 py-2"></textarea>
          <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description[0] }}</p>
        </div>

        <div class="flex items-start gap-4">
          <div class="flex-1">
            <label class="mb-1 block text-sm font-medium">Image</label>
            <input type="file" accept="image/*" @change="onFileChange" class="block w-full text-sm" />
            <p v-if="errors.image" class="mt-1 text-sm text-red-600">{{ errors.image[0] }}</p>
          </div>
          <img v-if="imagePreview" :src="imagePreview" alt="" class="h-16 w-16 rounded object-cover" />
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
          Active
        </label>

        <div class="flex justify-end gap-2">
          <button type="button" @click="showForm = false" class="rounded border px-4 py-2 text-sm">Cancel</button>
          <button :disabled="saving" class="rounded bg-blue-600 px-4 py-2 text-sm text-white disabled:opacity-50">
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
