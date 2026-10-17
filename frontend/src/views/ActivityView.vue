<script setup>
import { ref, watch, onMounted } from 'vue'
import { activityLogsApi } from '@/api/activityLogs'

const items = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const actionFilter = ref('')
const loading = ref(false)
const pageError = ref('')

const actionStyles = {
  created: 'bg-green-100 text-green-700',
  updated: 'bg-blue-100 text-blue-700',
  deleted: 'bg-red-100 text-red-700',
  force_deleted: 'bg-red-200 text-red-800',
  restored: 'bg-amber-100 text-amber-700',
}

const actionLabels = {
  created: 'Created',
  updated: 'Updated',
  deleted: 'Deleted',
  force_deleted: 'Deleted forever',
  restored: 'Restored',
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  return date.toLocaleString()
}

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await activityLogsApi.list({
      page: page.value,
      search: search.value || undefined,
      action: actionFilter.value || undefined,
    })
    items.value = data.data
    meta.value = data.meta
  } catch {
    pageError.value = 'Could not load the activity log.'
  } finally {
    loading.value = false
  }
}

let timer
watch([search, actionFilter], () => {
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

onMounted(load)
</script>

<template>
  <div>
    <h2 class="mb-4 text-xl font-semibold">Activity log</h2>

    <div class="mb-4 flex flex-wrap gap-3">
      <input
        v-model="search"
        type="text"
        aria-label="Search the activity description"
        placeholder="Search the description..."
        class="w-full max-w-xs rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      />
      <select
        v-model="actionFilter"
        aria-label="Filter by action"
        class="rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      >
        <option value="">All actions</option>
        <option value="created">Created</option>
        <option value="updated">Updated</option>
        <option value="deleted">Deleted</option>
        <option value="restored">Restored</option>
        <option value="force_deleted">Deleted forever</option>
      </select>
    </div>

    <p v-if="pageError" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700">
      {{ pageError }}
    </p>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
      <table class="w-full text-left text-sm">
        <thead class="border-b bg-gray-50 text-gray-600">
          <tr>
            <th scope="col" class="px-4 py-3">When</th>
            <th scope="col" class="px-4 py-3">User</th>
            <th scope="col" class="px-4 py-3">Action</th>
            <th scope="col" class="px-4 py-3">Description</th>
            <th scope="col" class="px-4 py-3">IP</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="5" class="px-4 py-6 text-center text-gray-500">No activity yet.</td>
          </tr>
          <tr v-else v-for="log in items" :key="log.id" class="border-b last:border-0">
            <td class="whitespace-nowrap px-4 py-3 text-gray-600">
              {{ formatDate(log.created_at) }}
            </td>
            <td class="px-4 py-3">{{ log.user_name }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="actionStyles[log.action] ?? 'bg-gray-200 text-gray-700'"
              >
                {{ actionLabels[log.action] ?? log.action }}
              </span>
            </td>
            <td class="px-4 py-3">{{ log.description }}</td>
            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ log.ip_address ?? '-' }}</td>
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
          class="rounded border bg-white px-3 py-1 disabled:opacity-50"
          @click="go(meta.current_page - 1)"
        >
          Previous
        </button>
        <button
          :disabled="meta.current_page >= meta.last_page"
          class="rounded border bg-white px-3 py-1 disabled:opacity-50"
          @click="go(meta.current_page + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>
