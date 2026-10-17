<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { categoriesApi } from '@/api/categories'
import { useToastStore } from '@/stores/toasts'
import { useAuthStore } from '@/stores/auth'

const toast = useToastStore()
const auth = useAuthStore()

// list state
const items = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const showDeleted = ref(false)
const loading = ref(false)
const pageError = ref('')

// all categories, for the parent dropdown
const options = ref([])

// form state
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', parent_id: '', description: '' })
const errors = ref({})
const saving = ref(false)

const parentChoices = computed(() => options.value.filter((o) => o.id !== editingId.value))

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await categoriesApi.list({
      page: page.value,
      search: search.value || undefined,
      trashed: showDeleted.value ? 1 : undefined,
    })
    items.value = data.data
    meta.value = data.meta
  } catch {
    pageError.value = 'Could not load categories.'
  } finally {
    loading.value = false
  }
}

async function loadOptions() {
  const { data } = await categoriesApi.all()
  options.value = data.data
}

// search with a small delay so we don't call the API on every keystroke
let timer
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    page.value = 1
    load()
  }, 300)
})

watch(showDeleted, () => {
  page.value = 1
  load()
})

// hide the "::deleted::<id>" suffix the backend adds on soft delete
function displayName(name) {
  return name ? name.replace(/::deleted::\d+$/, '') : name
}

function go(p) {
  page.value = p
  load()
}

function openCreate() {
  editingId.value = null
  Object.assign(form, { name: '', parent_id: '', description: '' })
  errors.value = {}
  showForm.value = true
}

function openEdit(c) {
  editingId.value = c.id
  Object.assign(form, {
    name: c.name,
    parent_id: c.parent_id ?? '',
    description: c.description ?? '',
  })
  errors.value = {}
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}

  const payload = {
    name: form.name,
    parent_id: form.parent_id || null,
    description: form.description || null,
  }

  try {
    if (editingId.value) {
      await categoriesApi.update(editingId.value, payload)
    } else {
      await categoriesApi.create(payload)
    }
    showForm.value = false
    toast.success(editingId.value ? 'Category updated.' : 'Category created.')
    await Promise.all([load(), loadOptions()])
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
    await categoriesApi.remove(c.id)
    if (items.value.length === 1 && page.value > 1) page.value--
    await Promise.all([load(), loadOptions()])
    toast.success('Category deleted.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Delete failed.')
  }
}

async function restoreCategory(c) {
  try {
    await categoriesApi.restore(c.id)
    await Promise.all([load(), loadOptions()])
    toast.success('Category restored.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Restore failed.')
  }
}

async function forceDeleteCategory(c) {
  if (!confirm(`Permanently delete "${displayName(c.name)}"? This cannot be undone.`)) return
  try {
    await categoriesApi.forceDelete(c.id)
    if (items.value.length === 1 && page.value > 1) page.value--
    await load()
    toast.success('Category permanently deleted.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Delete failed.')
  }
}

onMounted(() => {
  load()
  loadOptions()
})
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-xl font-semibold">Categories</h2>
      <button
        v-if="auth.canManage"
        @click="openCreate"
        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
      >
        New category
      </button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
      <input
        v-model="search"
        type="text"
        aria-label="Search categories"
        placeholder="Search categories..."
        class="w-full max-w-xs rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      />
      <label class="flex items-center gap-2 text-sm text-gray-700">
        <input v-model="showDeleted" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
        Show deleted
      </label>
    </div>

    <p v-if="pageError" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700">
      {{ pageError }}
    </p>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
      <table class="w-full text-left text-sm">
        <thead class="border-b bg-gray-50 text-gray-600">
          <tr>
            <th scope="col" class="px-4 py-3">Name</th>
            <th scope="col" class="px-4 py-3">Parent</th>
            <th scope="col" class="px-4 py-3">Products</th>
            <th scope="col" class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No categories found.</td>
          </tr>
          <tr v-else v-for="c in items" :key="c.id" class="border-b last:border-0">
            <td class="px-4 py-3 font-medium">{{ displayName(c.name) }}</td>
            <td class="px-4 py-3 text-gray-600">{{ c.parent?.name ?? '-' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ c.products_count }}</td>
            <td class="px-4 py-3 text-right">
              <template v-if="auth.canManage">
                <template v-if="showDeleted">
                  <button @click="restoreCategory(c)" class="mr-3 text-green-600 hover:underline">
                    Restore
                  </button>
                  <button @click="forceDeleteCategory(c)" class="text-red-600 hover:underline">
                    Delete forever
                  </button>
                </template>
                <template v-else>
                  <button @click="openEdit(c)" class="mr-3 text-blue-600 hover:underline">
                    Edit
                  </button>
                  <button @click="remove(c)" class="text-red-600 hover:underline">Delete</button>
                </template>
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

    <!-- create / edit modal -->
    <div
      v-if="showForm"
      role="dialog"
      aria-modal="true"
      aria-label="Category form"
      class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-4"
    >
      <form
        @submit.prevent="save"
        class="w-full max-w-md space-y-4 rounded-lg bg-white p-6 shadow-lg"
      >
        <h3 class="text-lg font-semibold">{{ editingId ? 'Edit category' : 'New category' }}</h3>

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
          <label class="mb-1 block text-sm font-medium">Parent category</label>
          <select v-model="form.parent_id" class="w-full rounded border border-gray-300 px-3 py-2">
            <option value="">None</option>
            <option v-for="o in parentChoices" :key="o.id" :value="o.id">{{ o.name }}</option>
          </select>
          <p v-if="errors.parent_id" class="mt-1 text-sm text-red-600">{{ errors.parent_id[0] }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full rounded border border-gray-300 px-3 py-2"
          ></textarea>
          <p v-if="errors.description" class="mt-1 text-sm text-red-600">
            {{ errors.description[0] }}
          </p>
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
