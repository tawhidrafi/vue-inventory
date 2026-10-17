<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { stocksApi } from '@/api/stocks'
import { stockMovementsApi } from '@/api/stockMovements'
import { productsApi } from '@/api/products'
import { warehousesApi } from '@/api/warehouses'
import { useToastStore } from '@/stores/toasts'
import { downloadResponse } from '@/utils/download'
import BarcodeScanInput from '@/components/BarcodeScanInput.vue'

const toast = useToastStore()

const items = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const warehouseFilter = ref('')
const lowOnly = ref(false)
const loading = ref(false)
const pageError = ref('')

const warehouses = ref([])

const typeLabels = {
  in: 'Stock in',
  out: 'Stock out',
  adjustment: 'Adjustment',
  transfer_in: 'Transfer in',
  transfer_out: 'Transfer out',
}

// ---- adjust modal ----
const showAdjust = ref(false)
const adjustTarget = ref('')
const adjustForm = reactive({
  product_id: null,
  warehouse_id: '',
  type: 'in',
  quantity: '',
  note: '',
})
const adjustErrors = ref({})
const savingAdjust = ref(false)

// ---- transfer modal ----
const showTransfer = ref(false)
const transferTarget = ref('')
const transferForm = reactive({
  product_id: null,
  from_warehouse_id: '',
  to_warehouse_id: '',
  quantity: '',
  note: '',
})
const transferErrors = ref({})
const savingTransfer = ref(false)

// ---- history panel ----
const showHistory = ref(false)
const historyTitle = ref('')
const historyItems = ref([])
const historyLoading = ref(false)

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await stocksApi.list({
      page: page.value,
      search: search.value || undefined,
      warehouse_id: warehouseFilter.value || undefined,
      low_stock: lowOnly.value ? 1 : undefined,
    })
    items.value = data.data
    meta.value = data.meta
  } catch {
    pageError.value = 'Could not load stock.'
  } finally {
    loading.value = false
  }
}

async function loadWarehouses() {
  const { data } = await warehousesApi.all()
  warehouses.value = data.data
}

let timer
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    page.value = 1
    load()
  }, 300)
})

watch([warehouseFilter, lowOnly], () => {
  page.value = 1
  load()
})

function go(p) {
  page.value = p
  load()
}

function openAdjust(row) {
  adjustTarget.value = `${row.product?.name} — ${row.product?.sku}`
  Object.assign(adjustForm, {
    product_id: row.product_id,
    warehouse_id: row.warehouse_id,
    type: 'in',
    quantity: '',
    note: '',
  })
  adjustErrors.value = {}
  showAdjust.value = true
}

async function submitAdjust() {
  savingAdjust.value = true
  adjustErrors.value = {}
  try {
    await stockMovementsApi.create({
      product_id: adjustForm.product_id,
      warehouse_id: adjustForm.warehouse_id,
      type: adjustForm.type,
      quantity: Number(adjustForm.quantity),
      note: adjustForm.note || null,
    })
    showAdjust.value = false
    toast.success('Stock adjusted.')
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      adjustErrors.value = e.response.data.errors ?? {}
    } else {
      adjustErrors.value = { quantity: ['Something went wrong. Please try again.'] }
    }
  } finally {
    savingAdjust.value = false
  }
}

function openTransfer(row) {
  transferTarget.value = row.product?.name
  Object.assign(transferForm, {
    product_id: row.product_id,
    from_warehouse_id: row.warehouse_id,
    to_warehouse_id: '',
    quantity: '',
    note: '',
  })
  transferErrors.value = {}
  showTransfer.value = true
}

async function submitTransfer() {
  savingTransfer.value = true
  transferErrors.value = {}
  try {
    await stockMovementsApi.transfer({
      product_id: transferForm.product_id,
      from_warehouse_id: transferForm.from_warehouse_id,
      to_warehouse_id: transferForm.to_warehouse_id,
      quantity: Number(transferForm.quantity),
      note: transferForm.note || null,
    })
    showTransfer.value = false
    toast.success('Stock transferred.')
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      transferErrors.value = e.response.data.errors ?? {}
    } else {
      transferErrors.value = { quantity: ['Something went wrong. Please try again.'] }
    }
  } finally {
    savingTransfer.value = false
  }
}

async function openHistory(row) {
  historyTitle.value = `${row.product?.name} — ${row.warehouse?.name}`
  historyItems.value = []
  showHistory.value = true
  historyLoading.value = true
  try {
    const { data } = await stockMovementsApi.list({
      product_id: row.product_id,
      warehouse_id: row.warehouse_id,
      per_page: 50,
    })
    historyItems.value = data.data
  } finally {
    historyLoading.value = false
  }
}

async function exportMovements() {
  try {
    const response = await stockMovementsApi.exportCsv({
      warehouse_id: warehouseFilter.value || undefined,
    })
    downloadResponse(response.data, 'stock-movements.csv')
    toast.success('Export ready.')
  } catch {
    toast.error('Export failed.')
  }
}

// a barcode scanner types the code and hits Enter — open the adjust modal for it
async function handleScan(code) {
  try {
    const { data } = await productsApi.lookup(code)
    const product = data.data

    const preferred =
      warehouses.value.find((w) => String(w.id) === String(warehouseFilter.value)) ??
      warehouses.value[0]

    adjustTarget.value = `${product.name} — ${product.sku}`
    Object.assign(adjustForm, {
      product_id: product.id,
      warehouse_id: preferred ? preferred.id : '',
      type: 'in',
      quantity: '',
      note: '',
    })
    adjustErrors.value = {}
    showAdjust.value = true
  } catch (e) {
    if (e.response?.status === 404) {
      toast.error(`No product matches "${code}".`)
    } else {
      toast.error('Barcode lookup failed.')
    }
  }
}

onMounted(() => {
  load()
  loadWarehouses()
})
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-xl font-semibold">Stock</h2>
      <button
        @click="exportMovements"
        class="rounded border bg-white px-4 py-2 text-sm hover:bg-gray-50"
      >
        Export movements CSV
      </button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
      <BarcodeScanInput class="w-full max-w-xs" @scan="handleScan" />
      <input
        v-model="search"
        type="text"
        aria-label="Search stock by product name or SKU"
        placeholder="Search by product name or SKU..."
        class="w-full max-w-xs rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      />
      <select
        v-model="warehouseFilter"
        aria-label="Filter by warehouse"
        class="rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      >
        <option value="">All warehouses</option>
        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
      </select>
      <label class="flex items-center gap-2 text-sm text-gray-700">
        <input v-model="lowOnly" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
        Low stock only
      </label>
    </div>

    <p v-if="pageError" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700">
      {{ pageError }}
    </p>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
      <table class="w-full text-left text-sm">
        <thead class="border-b bg-gray-50 text-gray-600">
          <tr>
            <th scope="col" class="px-4 py-3">Product</th>
            <th scope="col" class="px-4 py-3">SKU</th>
            <th scope="col" class="px-4 py-3">Warehouse</th>
            <th scope="col" class="px-4 py-3">Quantity</th>
            <th scope="col" class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="5" class="px-4 py-6 text-center text-gray-500">No stock records found.</td>
          </tr>
          <tr v-else v-for="s in items" :key="s.id" class="border-b last:border-0">
            <td class="px-4 py-3 font-medium">{{ s.product?.name ?? '-' }}</td>
            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ s.product?.sku ?? '-' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ s.warehouse?.name ?? '-' }}</td>
            <td class="px-4 py-3 font-semibold">{{ s.quantity }}</td>
            <td class="px-4 py-3 text-right">
              <button @click="openAdjust(s)" class="mr-3 text-blue-600 hover:underline">
                Adjust
              </button>
              <button @click="openTransfer(s)" class="mr-3 text-blue-600 hover:underline">
                Transfer
              </button>
              <button @click="openHistory(s)" class="text-gray-600 hover:underline">History</button>
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

    <!-- adjust modal -->
    <div
      v-if="showAdjust"
      role="dialog"
      aria-modal="true"
      aria-label="Adjust stock"
      class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-4"
    >
      <form
        @submit.prevent="submitAdjust"
        class="w-full max-w-md space-y-4 rounded-lg bg-white p-6 shadow-lg"
      >
        <h3 class="text-lg font-semibold">Adjust stock</h3>
        <p class="text-sm text-gray-500">{{ adjustTarget }}</p>

        <div>
          <label class="mb-1 block text-sm font-medium">Warehouse</label>
          <select
            v-model="adjustForm.warehouse_id"
            class="w-full rounded border border-gray-300 bg-white px-3 py-2"
          >
            <option value="">Select warehouse</option>
            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
          </select>
          <p v-if="adjustErrors.warehouse_id" class="mt-1 text-sm text-red-600">
            {{ adjustErrors.warehouse_id[0] }}
          </p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Type</label>
          <select
            v-model="adjustForm.type"
            class="w-full rounded border border-gray-300 bg-white px-3 py-2"
          >
            <option value="in">Stock in</option>
            <option value="out">Stock out</option>
            <option value="adjustment">Adjustment (+/-)</option>
          </select>
          <p v-if="adjustErrors.type" class="mt-1 text-sm text-red-600">
            {{ adjustErrors.type[0] }}
          </p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">
            Quantity
            <span v-if="adjustForm.type === 'adjustment'" class="text-gray-400"
              >(signed, e.g. -3)</span
            >
          </label>
          <input
            v-model="adjustForm.quantity"
            type="number"
            class="w-full rounded border border-gray-300 px-3 py-2"
          />
          <p v-if="adjustErrors.quantity" class="mt-1 text-sm text-red-600">
            {{ adjustErrors.quantity[0] }}
          </p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">
            Note
            <span v-if="adjustForm.type === 'adjustment'" class="text-red-500">*</span>
          </label>
          <textarea
            v-model="adjustForm.note"
            rows="2"
            class="w-full rounded border border-gray-300 px-3 py-2"
          ></textarea>
          <p v-if="adjustErrors.note" class="mt-1 text-sm text-red-600">
            {{ adjustErrors.note[0] }}
          </p>
        </div>

        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="showAdjust = false"
            class="rounded border px-4 py-2 text-sm"
          >
            Cancel
          </button>
          <button
            :disabled="savingAdjust"
            class="rounded bg-blue-600 px-4 py-2 text-sm text-white disabled:opacity-50"
          >
            {{ savingAdjust ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </form>
    </div>

    <!-- transfer modal -->
    <div
      v-if="showTransfer"
      role="dialog"
      aria-modal="true"
      aria-label="Transfer stock"
      class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-4"
    >
      <form
        @submit.prevent="submitTransfer"
        class="w-full max-w-md space-y-4 rounded-lg bg-white p-6 shadow-lg"
      >
        <h3 class="text-lg font-semibold">Transfer stock</h3>
        <p class="text-sm text-gray-500">{{ transferTarget }}</p>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1 block text-sm font-medium">From</label>
            <select
              v-model="transferForm.from_warehouse_id"
              class="w-full rounded border border-gray-300 bg-white px-3 py-2"
            >
              <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">To</label>
            <select
              v-model="transferForm.to_warehouse_id"
              class="w-full rounded border border-gray-300 bg-white px-3 py-2"
            >
              <option value="">Select warehouse</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
            <p v-if="transferErrors.to_warehouse_id" class="mt-1 text-sm text-red-600">
              {{ transferErrors.to_warehouse_id[0] }}
            </p>
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Quantity</label>
          <input
            v-model="transferForm.quantity"
            type="number"
            min="1"
            class="w-full rounded border border-gray-300 px-3 py-2"
          />
          <p v-if="transferErrors.quantity" class="mt-1 text-sm text-red-600">
            {{ transferErrors.quantity[0] }}
          </p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Note</label>
          <textarea
            v-model="transferForm.note"
            rows="2"
            class="w-full rounded border border-gray-300 px-3 py-2"
          ></textarea>
        </div>

        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="showTransfer = false"
            class="rounded border px-4 py-2 text-sm"
          >
            Cancel
          </button>
          <button
            :disabled="savingTransfer"
            class="rounded bg-blue-600 px-4 py-2 text-sm text-white disabled:opacity-50"
          >
            {{ savingTransfer ? 'Transferring...' : 'Transfer' }}
          </button>
        </div>
      </form>
    </div>

    <!-- history panel -->
    <div
      v-if="showHistory"
      role="dialog"
      aria-modal="true"
      aria-label="Stock movement history"
      class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-4"
    >
      <div class="flex max-h-[80vh] w-full max-w-2xl flex-col rounded-lg bg-white p-6 shadow-lg">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold">Movement history</h3>
            <p class="text-sm text-gray-500">{{ historyTitle }}</p>
          </div>
          <button @click="showHistory = false" class="rounded border px-3 py-1 text-sm">
            Close
          </button>
        </div>

        <div class="overflow-y-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b bg-gray-50 text-gray-600">
              <tr>
                <th scope="col" class="px-3 py-2">Date</th>
                <th scope="col" class="px-3 py-2">Type</th>
                <th scope="col" class="px-3 py-2">Change</th>
                <th scope="col" class="px-3 py-2">Balance</th>
                <th scope="col" class="px-3 py-2">By</th>
                <th scope="col" class="px-3 py-2">Note</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="historyLoading">
                <td colspan="6" class="px-3 py-4 text-center text-gray-500">Loading...</td>
              </tr>
              <tr v-else-if="!historyItems.length">
                <td colspan="6" class="px-3 py-4 text-center text-gray-500">No movements yet.</td>
              </tr>
              <tr v-else v-for="m in historyItems" :key="m.id" class="border-b last:border-0">
                <td class="px-3 py-2 text-gray-600">
                  {{ new Date(m.created_at).toLocaleString() }}
                </td>
                <td class="px-3 py-2">{{ typeLabels[m.type] ?? m.type }}</td>
                <td
                  class="px-3 py-2 font-semibold"
                  :class="m.quantity < 0 ? 'text-red-600' : 'text-green-600'"
                >
                  {{ m.quantity > 0 ? '+' : '' }}{{ m.quantity }}
                </td>
                <td class="px-3 py-2">{{ m.balance_after }}</td>
                <td class="px-3 py-2 text-gray-600">{{ m.user_name ?? '-' }}</td>
                <td class="px-3 py-2 text-gray-600">{{ m.note ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
