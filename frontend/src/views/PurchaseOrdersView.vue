<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { purchaseOrdersApi } from '@/api/purchaseOrders'
import { suppliersApi } from '@/api/suppliers'
import { warehousesApi } from '@/api/warehouses'
import { productsApi } from '@/api/products'
import { useToastStore } from '@/stores/toasts'
import { useAuthStore } from '@/stores/auth'

const toast = useToastStore()
const auth = useAuthStore()

const items = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const supplierFilter = ref('')
const statusFilter = ref('')
const loading = ref(false)
const pageError = ref('')

const suppliers = ref([])
const warehouses = ref([])
const allProducts = ref([])
const linkProducts = ref([])

const statusStyles = {
  draft: 'bg-gray-200 text-gray-700',
  received: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
  partially_received: 'bg-amber-100 text-amber-700',
}

const statusLabels = {
  draft: 'Draft',
  received: 'Received',
  cancelled: 'Cancelled',
  partially_received: 'Partial',
}

// product options for the line rows: the chosen supplier's linked products
// when available, otherwise every product.
const productOptions = computed(() => {
  const source = linkProducts.value.length ? linkProducts.value : allProducts.value
  return source.map((p) => ({ id: p.id, name: p.name, sku: p.sku, unit_cost: p.unit_cost ?? null }))
})

// ---- create / edit modal ----
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ supplier_id: '', expected_at: '', notes: '', items: [] })
const errors = ref({})
const saving = ref(false)

// ---- receive modal ----
const showReceive = ref(false)
const receiveTarget = ref(null)
const receiveForm = reactive({ warehouse_id: '', update_cost: false })
const receiveLines = ref([])
const receiveErrors = ref({})
const savingReceive = ref(false)

const receiveErrorList = computed(() => Object.values(receiveErrors.value).flat())

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await purchaseOrdersApi.list({
      page: page.value,
      search: search.value || undefined,
      supplier_id: supplierFilter.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = data.data
    meta.value = data.meta
  } catch {
    pageError.value = 'Could not load purchase orders.'
  } finally {
    loading.value = false
  }
}

async function loadLookups() {
  const [s, w, p] = await Promise.all([suppliersApi.all(), warehousesApi.all(), productsApi.all()])
  suppliers.value = s.data.data
  warehouses.value = w.data.data
  allProducts.value = p.data.data
}

let timer
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    page.value = 1
    load()
  }, 300)
})

watch([supplierFilter, statusFilter], () => {
  page.value = 1
  load()
})

function go(p) {
  page.value = p
  load()
}

function emptyLine() {
  return { product_id: '', quantity: 1, unit_cost: '' }
}

function resetForm() {
  Object.assign(form, { supplier_id: '', expected_at: '', notes: '', items: [emptyLine()] })
  linkProducts.value = []
  errors.value = {}
}

function onSupplierChange() {
  linkProducts.value = []
  if (!form.supplier_id) return
  suppliersApi.products(form.supplier_id).then(({ data }) => {
    linkProducts.value = data.data
  })
}

function onProductChange(line) {
  const opt = productOptions.value.find((o) => o.id === Number(line.product_id))
  if (opt && opt.unit_cost != null) line.unit_cost = opt.unit_cost
}

function addLine() {
  form.items.push(emptyLine())
}

function removeLine(index) {
  form.items.splice(index, 1)
  if (!form.items.length) form.items.push(emptyLine())
}

async function openCreate() {
  editingId.value = null
  resetForm()
  showForm.value = true
}

async function openEdit(po) {
  editingId.value = po.id
  resetForm()
  const { data } = await purchaseOrdersApi.show(po.id)
  const d = data.data
  form.supplier_id = d.supplier_id
  form.expected_at = d.expected_at ? d.expected_at.substring(0, 10) : ''
  form.notes = d.notes ?? ''
  form.items = (d.items ?? []).map((i) => ({
    product_id: i.product_id,
    quantity: i.quantity,
    unit_cost: i.unit_cost,
  }))
  await onSupplierChange()
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}

  const payload = {
    supplier_id: Number(form.supplier_id),
    expected_at: form.expected_at || null,
    notes: form.notes || null,
    items: form.items
      .filter((l) => l.product_id)
      .map((l) => ({
        product_id: Number(l.product_id),
        quantity: Number(l.quantity),
        unit_cost: l.unit_cost === '' ? 0 : Number(l.unit_cost),
      })),
  }

  try {
    if (editingId.value) {
      await purchaseOrdersApi.update(editingId.value, payload)
    } else {
      await purchaseOrdersApi.create(payload)
    }
    showForm.value = false
    toast.success(editingId.value ? 'Purchase order updated.' : 'Purchase order created.')
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    } else {
      errors.value = { supplier_id: ['Something went wrong. Please try again.'] }
    }
  } finally {
    saving.value = false
  }
}

async function openReceive(po) {
  receiveTarget.value = po
  receiveForm.warehouse_id = ''
  receiveForm.update_cost = false
  receiveLines.value = []
  receiveErrors.value = {}
  showReceive.value = true

  try {
    const { data } = await purchaseOrdersApi.show(po.id)
    receiveLines.value = (data.data.items ?? []).map((i) => ({
      id: i.id,
      product_name: i.product_name,
      product_sku: i.product_sku,
      ordered: i.quantity,
      received: i.quantity_received,
      remaining: i.quantity_remaining,
      quantity: i.quantity_remaining,
    }))
  } catch {
    receiveErrors.value = { warehouse_id: ['Could not load the order lines.'] }
  }
}

function receiveAll() {
  receiveLines.value.forEach((line) => {
    line.quantity = line.remaining
  })
}

async function submitReceive() {
  savingReceive.value = true
  receiveErrors.value = {}
  try {
    await purchaseOrdersApi.receive(receiveTarget.value.id, {
      warehouse_id: Number(receiveForm.warehouse_id),
      update_cost: receiveForm.update_cost,
      items: receiveLines.value.map((l) => ({ id: l.id, quantity: Number(l.quantity) || 0 })),
    })
    showReceive.value = false
    toast.success('Stock received.')
    await load()
  } catch (e) {
    receiveErrors.value = { warehouse_id: ['Something went wrong. Please try again.'] }

    if (e.response?.status === 422) {
      receiveErrors.value = e.response.data.errors ?? {}
      // the backend reports quantity problems under a custom "message" key
      if (!Object.keys(receiveErrors.value).length && e.response.data.message) {
        receiveErrors.value = { items: [e.response.data.message] }
      }
    }
  } finally {
    savingReceive.value = false
  }
}

async function cancelPo(po) {
  if (!confirm(`Cancel ${po.number}?`)) return
  try {
    await purchaseOrdersApi.cancel(po.id)
    await load()
    toast.success(`${po.number} cancelled.`)
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Cancel failed.')
  }
}

async function remove(po) {
  if (!confirm(`Delete ${po.number}?`)) return
  try {
    await purchaseOrdersApi.remove(po.id)
    if (items.value.length === 1 && page.value > 1) page.value--
    await load()
    toast.success(`${po.number} deleted.`)
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Delete failed.')
  }
}

onMounted(() => {
  load()
  loadLookups()
})
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-xl font-semibold">Purchase Orders</h2>
      <button
        v-if="auth.canManage"
        @click="openCreate"
        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
      >
        New purchase order
      </button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
      <input
        v-model="search"
        type="text"
        aria-label="Search purchase orders by number"
        placeholder="Search by PO number..."
        class="w-full max-w-xs rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      />
      <select
        v-model="supplierFilter"
        aria-label="Filter by supplier"
        class="rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      >
        <option value="">All suppliers</option>
        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
      </select>
      <select
        v-model="statusFilter"
        aria-label="Filter by status"
        class="rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      >
        <option value="">All statuses</option>
        <option value="draft">Draft</option>
        <option value="partially_received">Partial</option>
        <option value="received">Received</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <p v-if="pageError" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700">
      {{ pageError }}
    </p>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
      <table class="w-full text-left text-sm">
        <thead class="border-b bg-gray-50 text-gray-600">
          <tr>
            <th scope="col" class="px-4 py-3">Number</th>
            <th scope="col" class="px-4 py-3">Supplier</th>
            <th scope="col" class="px-4 py-3">Status</th>
            <th scope="col" class="px-4 py-3">Items</th>
            <th scope="col" class="px-4 py-3">Total</th>
            <th scope="col" class="px-4 py-3">Expected</th>
            <th scope="col" class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
              No purchase orders found.
            </td>
          </tr>
          <tr v-else v-for="po in items" :key="po.id" class="border-b last:border-0">
            <td class="px-4 py-3 font-mono text-xs font-medium">{{ po.number }}</td>
            <td class="px-4 py-3 text-gray-700">{{ po.supplier?.name ?? '-' }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="statusStyles[po.status]"
              >
                {{ statusLabels[po.status] ?? po.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-600">{{ po.items_count }}</td>
            <td class="px-4 py-3 text-gray-600">{{ Number(po.total).toFixed(2) }}</td>
            <td class="px-4 py-3 text-gray-600">
              {{ po.expected_at ? po.expected_at.substring(0, 10) : '-' }}
            </td>
            <td class="px-4 py-3 text-right">
              <RouterLink
                :to="{ name: 'purchase-order-print', params: { id: po.id } }"
                target="_blank"
                class="mr-3 text-gray-700 hover:underline"
              >
                Print
              </RouterLink>
              <button
                v-if="po.status === 'draft' || po.status === 'partially_received'"
                @click="openReceive(po)"
                class="mr-3 text-green-600 hover:underline"
              >
                Receive
              </button>
              <template v-if="auth.canManage">
                <button
                  v-if="po.status === 'draft' || po.status === 'partially_received'"
                  @click="cancelPo(po)"
                  class="mr-3 text-gray-600 hover:underline"
                >
                  Cancel
                </button>
                <template v-if="po.status === 'draft'">
                  <button @click="openEdit(po)" class="mr-3 text-blue-600 hover:underline">
                    Edit
                  </button>
                  <button @click="remove(po)" class="text-red-600 hover:underline">Delete</button>
                </template>
              </template>
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
      aria-label="Purchase order form"
      class="fixed inset-0 z-10 flex items-center justify-center overflow-y-auto bg-black/40 p-4"
    >
      <form
        @submit.prevent="save"
        class="my-8 w-full max-w-3xl space-y-4 rounded-lg bg-white p-6 shadow-lg"
      >
        <h3 class="text-lg font-semibold">
          {{ editingId ? 'Edit purchase order' : 'New purchase order' }}
        </h3>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1 block text-sm font-medium">Supplier</label>
            <select
              v-model="form.supplier_id"
              @change="onSupplierChange"
              class="w-full rounded border border-gray-300 bg-white px-3 py-2"
            >
              <option value="">Select supplier</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <p v-if="errors.supplier_id" class="mt-1 text-sm text-red-600">
              {{ errors.supplier_id[0] }}
            </p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium">Expected date</label>
            <input
              v-model="form.expected_at"
              type="date"
              class="w-full rounded border border-gray-300 px-3 py-2"
            />
            <p v-if="errors.expected_at" class="mt-1 text-sm text-red-600">
              {{ errors.expected_at[0] }}
            </p>
          </div>
        </div>

        <div>
          <div class="mb-2 flex items-center justify-between">
            <label class="text-sm font-medium">Items</label>
            <button type="button" @click="addLine" class="rounded border px-3 py-1 text-xs">
              Add line
            </button>
          </div>
          <p v-if="errors.items" class="mb-2 text-sm text-red-600">{{ errors.items[0] }}</p>

          <div class="space-y-2">
            <div v-for="(line, index) in form.items" :key="index" class="flex items-center gap-2">
              <select
                v-model="line.product_id"
                aria-label="Line product"
                @change="onProductChange(line)"
                class="flex-1 rounded border border-gray-300 bg-white px-3 py-2 text-sm"
              >
                <option value="">Select product</option>
                <option v-for="o in productOptions" :key="o.id" :value="o.id">
                  {{ o.name }}{{ o.sku ? ` (${o.sku})` : '' }}
                </option>
              </select>
              <input
                v-model="line.quantity"
                type="number"
                min="1"
                aria-label="Line quantity"
                placeholder="Qty"
                class="w-24 rounded border border-gray-300 px-3 py-2 text-sm"
              />
              <input
                v-model="line.unit_cost"
                type="number"
                step="0.01"
                min="0"
                aria-label="Line unit cost"
                placeholder="Unit cost"
                class="w-28 rounded border border-gray-300 px-3 py-2 text-sm"
              />
              <button
                type="button"
                aria-label="Remove line"
                class="text-red-600 hover:underline"
                @click="removeLine(index)"
              >
                ✕
              </button>
            </div>
          </div>
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

    <!-- receive modal -->
    <div
      v-if="showReceive"
      role="dialog"
      aria-modal="true"
      aria-label="Receive purchase order"
      class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-4"
    >
      <form
        @submit.prevent="submitReceive"
        class="w-full max-w-lg space-y-4 rounded-lg bg-white p-6 shadow-lg"
      >
        <h3 class="text-lg font-semibold">Receive purchase order</h3>
        <p class="text-sm text-gray-500">
          Receive items of <span class="font-mono">{{ receiveTarget?.number }}</span> into a
          warehouse. This increases stock.
        </p>

        <div>
          <label class="mb-1 block text-sm font-medium">Warehouse</label>
          <select
            v-model="receiveForm.warehouse_id"
            class="w-full rounded border border-gray-300 bg-white px-3 py-2"
          >
            <option value="">Select warehouse</option>
            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
          </select>
        </div>

        <div>
          <div class="mb-2 flex items-center justify-between">
            <label class="text-sm font-medium">Items to receive</label>
            <button type="button" @click="receiveAll" class="rounded border px-3 py-1 text-xs">
              Receive all
            </button>
          </div>

          <div class="space-y-2">
            <div
              v-for="line in receiveLines"
              :key="line.id"
              class="flex items-center gap-3 rounded border border-gray-200 px-3 py-2 text-sm"
            >
              <div class="flex-1">
                <div class="font-medium">{{ line.product_name }}</div>
                <div class="text-xs text-gray-500">
                  Ordered {{ line.ordered }} · received {{ line.received }} ·
                  {{ line.remaining }} left
                </div>
              </div>
              <input
                v-model.number="line.quantity"
                type="number"
                min="0"
                :max="line.remaining"
                :disabled="line.remaining === 0"
                class="w-20 rounded border border-gray-300 px-2 py-1 text-sm disabled:bg-gray-100"
              />
            </div>
          </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
          <input
            v-model="receiveForm.update_cost"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300"
          />
          Update each product's cost price to the ordered unit cost
        </label>

        <ul
          v-if="receiveErrorList.length"
          class="space-y-1 rounded bg-red-50 px-3 py-2 text-sm text-red-700"
        >
          <li v-for="(msg, index) in receiveErrorList" :key="index">{{ msg }}</li>
        </ul>

        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="showReceive = false"
            class="rounded border px-4 py-2 text-sm"
          >
            Cancel
          </button>
          <button
            :disabled="savingReceive"
            class="rounded bg-green-600 px-4 py-2 text-sm text-white disabled:opacity-50"
          >
            {{ savingReceive ? 'Receiving...' : 'Receive' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
