<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { salesOrdersApi } from '@/api/salesOrders'
import { customersApi } from '@/api/customers'
import { warehousesApi } from '@/api/warehouses'
import { productsApi } from '@/api/products'
import { useToastStore } from '@/stores/toasts'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToastStore()
const auth = useAuthStore()

const items = ref([])
const meta = ref(null)
const page = ref(1)
const search = ref('')
const customerFilter = ref('')
const statusFilter = ref('')
const loading = ref(false)
const pageError = ref('')

const customers = ref([])
const warehouses = ref([])
const allProducts = ref([])

const statusStyles = {
  draft: 'bg-gray-200 text-gray-700',
  confirmed: 'bg-blue-100 text-blue-700',
  partially_shipped: 'bg-amber-100 text-amber-700',
  shipped: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
}

const statusLabels = {
  draft: 'Draft',
  confirmed: 'Confirmed',
  partially_shipped: 'Partial',
  shipped: 'Shipped',
  cancelled: 'Cancelled',
}

function money(value) {
  return `$${Number(value ?? 0).toFixed(2)}`
}

// ---- create / edit modal ----
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({
  customer_id: '',
  order_date: '',
  expected_ship_date: '',
  shipping_address: '',
  notes: '',
  discount: '',
  tax: '',
  items: [],
})
const errors = ref({})
const saving = ref(false)

// live form subtotal & total
const formSubtotal = computed(() => {
  return form.items.reduce((sum, line) => {
    const qty = Number(line.quantity) || 0
    const price = Number(line.unit_price) || 0
    return sum + qty * price
  }, 0)
})

const formTotal = computed(() => {
  const discount = Number(form.discount) || 0
  const tax = Number(form.tax) || 0
  return Math.max(0, formSubtotal.value - discount + tax)
})

// ---- ship / dispatch modal ----
const showShip = ref(false)
const shipTarget = ref(null)
const shipForm = reactive({ warehouse_id: '' })
const shipLines = ref([])
const shipErrors = ref({})
const savingShip = ref(false)

const shipErrorList = computed(() => Object.values(shipErrors.value).flat())

async function load() {
  loading.value = true
  pageError.value = ''
  try {
    const { data } = await salesOrdersApi.list({
      page: page.value,
      search: search.value || undefined,
      customer_id: customerFilter.value || undefined,
      status: statusFilter.value || undefined,
    })
    items.value = data.data
    meta.value = data.meta
  } catch {
    pageError.value = 'Could not load sales orders.'
  } finally {
    loading.value = false
  }
}

async function loadLookups() {
  const [c, w, p] = await Promise.all([customersApi.all(), warehousesApi.all(), productsApi.all()])
  customers.value = c.data.data
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

watch([customerFilter, statusFilter], () => {
  page.value = 1
  load()
})

function go(p) {
  page.value = p
  load()
}

function emptyLine() {
  return { product_id: '', quantity: 1, unit_price: '' }
}

function resetForm() {
  const today = new Date().toISOString().substring(0, 10)
  Object.assign(form, {
    customer_id: '',
    order_date: today,
    expected_ship_date: '',
    shipping_address: '',
    notes: '',
    discount: '',
    tax: '',
    items: [emptyLine()],
  })
  errors.value = {}
}

function onProductChange(line) {
  const opt = allProducts.value.find((o) => o.id === Number(line.product_id))
  if (opt && opt.sell_price != null) {
    line.unit_price = opt.sell_price
  }
}

function addLine() {
  form.items.push(emptyLine())
}

function removeLine(index) {
  form.items.splice(index, 1)
  if (!form.items.length) form.items.push(emptyLine())
}

function openCreate() {
  editingId.value = null
  resetForm()
  showForm.value = true
}

async function openEdit(so) {
  editingId.value = so.id
  resetForm()
  const { data } = await salesOrdersApi.show(so.id)
  const d = data.data
  form.customer_id = d.customer_id
  form.order_date = d.order_date ? d.order_date.substring(0, 10) : ''
  form.expected_ship_date = d.expected_ship_date ? d.expected_ship_date.substring(0, 10) : ''
  form.shipping_address = d.shipping_address ?? ''
  form.notes = d.notes ?? ''
  form.discount = d.discount ? Number(d.discount) : ''
  form.tax = d.tax ? Number(d.tax) : ''
  form.items = (d.items ?? []).map((i) => ({
    product_id: i.product_id,
    quantity: i.quantity,
    unit_price: i.unit_price,
  }))
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}

  const payload = {
    customer_id: Number(form.customer_id),
    order_date: form.order_date || null,
    expected_ship_date: form.expected_ship_date || null,
    shipping_address: form.shipping_address || null,
    notes: form.notes || null,
    discount: form.discount === '' ? 0 : Number(form.discount),
    tax: form.tax === '' ? 0 : Number(form.tax),
    items: form.items
      .filter((l) => l.product_id)
      .map((l) => ({
        product_id: Number(l.product_id),
        quantity: Number(l.quantity),
        unit_price: l.unit_price === '' ? 0 : Number(l.unit_price),
      })),
  }

  try {
    if (editingId.value) {
      await salesOrdersApi.update(editingId.value, payload)
    } else {
      await salesOrdersApi.create(payload)
    }
    showForm.value = false
    toast.success(editingId.value ? 'Sales order updated.' : 'Sales order created.')
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors ?? {}
    } else {
      errors.value = { customer_id: ['Something went wrong. Please try again.'] }
    }
  } finally {
    saving.value = false
  }
}

async function confirmOrder(so) {
  try {
    await salesOrdersApi.confirm(so.id)
    toast.success('Sales order confirmed.')
    await load()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Confirm failed.')
  }
}

async function openShip(so) {
  shipTarget.value = so
  shipForm.warehouse_id = ''
  shipLines.value = []
  shipErrors.value = {}
  showShip.value = true

  try {
    const { data } = await salesOrdersApi.show(so.id)
    shipLines.value = (data.data.items ?? []).map((i) => ({
      id: i.id,
      product_name: i.product_name,
      product_sku: i.product_sku,
      ordered: i.quantity,
      shipped: i.quantity_shipped,
      remaining: i.quantity_remaining,
      quantity: i.quantity_remaining,
    }))
  } catch {
    shipErrors.value = { warehouse_id: ['Could not load the order lines.'] }
  }
}

function shipAll() {
  shipLines.value.forEach((line) => {
    line.quantity = line.remaining
  })
}

async function submitShip() {
  savingShip.value = true
  shipErrors.value = {}
  try {
    await salesOrdersApi.ship(shipTarget.value.id, {
      warehouse_id: Number(shipForm.warehouse_id),
      items: shipLines.value.map((l) => ({ id: l.id, quantity: Number(l.quantity) || 0 })),
    })
    showShip.value = false
    toast.success('Stock dispatched successfully.')
    await load()
  } catch (e) {
    shipErrors.value = { warehouse_id: ['Something went wrong. Please try again.'] }

    if (e.response?.status === 422) {
      shipErrors.value = e.response.data.errors ?? {}
      if (!Object.keys(shipErrors.value).length && e.response.data.message) {
        shipErrors.value = { items: [e.response.data.message] }
      }
    }
  } finally {
    savingShip.value = false
  }
}

async function cancelOrder(so) {
  if (!confirm(`Cancel ${so.number}?`)) return
  try {
    await salesOrdersApi.cancel(so.id)
    toast.success('Sales order cancelled.')
    await load()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Cancel failed.')
  }
}

async function removeOrder(so) {
  if (!confirm(`Delete draft ${so.number}?`)) return
  try {
    await salesOrdersApi.remove(so.id)
    if (items.value.length === 1 && page.value > 1) page.value--
    await load()
    toast.success('Sales order deleted.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Delete failed.')
  }
}

function printOrder(so) {
  router.push({ name: 'sales-order-print', params: { id: so.id } })
}

onMounted(() => {
  load()
  loadLookups()
})
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-xl font-semibold">Sales Orders</h2>
      <button
        v-if="auth.canManage"
        @click="openCreate"
        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
      >
        New sales order
      </button>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <input
        v-model="search"
        type="text"
        aria-label="Search by order number"
        placeholder="Search SO number..."
        class="w-full max-w-xs rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      />
      <select
        v-model="customerFilter"
        aria-label="Filter by customer"
        class="rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      >
        <option value="">All customers</option>
        <option v-for="c in customers" :key="c.id" :value="c.id">
          {{ c.name }}{{ c.company_name ? ` (${c.company_name})` : '' }}
        </option>
      </select>
      <select
        v-model="statusFilter"
        aria-label="Filter by status"
        class="rounded border border-gray-300 bg-white px-3 py-2 text-sm"
      >
        <option value="">All statuses</option>
        <option value="draft">Draft</option>
        <option value="confirmed">Confirmed</option>
        <option value="partially_shipped">Partially shipped</option>
        <option value="shipped">Shipped</option>
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
            <th scope="col" class="px-4 py-3">Order #</th>
            <th scope="col" class="px-4 py-3">Customer</th>
            <th scope="col" class="px-4 py-3">Order Date</th>
            <th scope="col" class="px-4 py-3">Lines</th>
            <th scope="col" class="px-4 py-3">Total</th>
            <th scope="col" class="px-4 py-3">Status</th>
            <th scope="col" class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-6 text-center text-gray-500">Loading...</td>
          </tr>
          <tr v-else-if="!items.length">
            <td colspan="7" class="px-4 py-6 text-center text-gray-500">No sales orders found.</td>
          </tr>
          <tr v-else v-for="so in items" :key="so.id" class="border-b last:border-0">
            <td class="px-4 py-3 font-mono text-xs font-semibold">{{ so.number }}</td>
            <td class="px-4 py-3">
              <span class="font-medium">{{ so.customer?.name ?? '-' }}</span>
              <span v-if="so.customer?.company_name" class="block text-xs text-gray-500">
                {{ so.customer.company_name }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-600">
              {{ so.order_date ? so.order_date.substring(0, 10) : '-' }}
            </td>
            <td class="px-4 py-3 text-gray-600">{{ so.items_count }}</td>
            <td class="px-4 py-3 font-medium">{{ money(so.total) }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="statusStyles[so.status] || 'bg-gray-100'"
              >
                {{ statusLabels[so.status] ?? so.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button @click="printOrder(so)" class="mr-3 text-gray-600 hover:underline">
                Print
              </button>

              <template v-if="so.status === 'draft' && auth.canManage">
                <button @click="confirmOrder(so)" class="mr-3 text-blue-600 hover:underline">
                  Confirm
                </button>
              </template>

              <template v-if="['draft', 'confirmed', 'partially_shipped'].includes(so.status)">
                <button @click="openShip(so)" class="mr-3 text-green-600 hover:underline">
                  Ship
                </button>
              </template>

              <template v-if="so.status === 'draft' && auth.canManage">
                <button @click="openEdit(so)" class="mr-3 text-blue-600 hover:underline">
                  Edit
                </button>
                <button @click="removeOrder(so)" class="mr-3 text-red-600 hover:underline">
                  Delete
                </button>
              </template>

              <template v-if="['draft', 'confirmed'].includes(so.status) && auth.canManage">
                <button @click="cancelOrder(so)" class="text-red-600 hover:underline">
                  Cancel
                </button>
              </template>
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

    <!-- Create / Edit Modal -->
    <div
      v-if="showForm"
      role="dialog"
      aria-modal="true"
      aria-label="Sales order form"
      class="fixed inset-0 z-10 flex items-center justify-center overflow-y-auto bg-black/40 p-4"
    >
      <form
        @submit.prevent="save"
        class="my-8 flex max-h-[90vh] w-full max-w-3xl flex-col rounded-lg bg-white p-6 shadow-lg"
      >
        <h3 class="mb-4 text-lg font-semibold">
          {{ editingId ? 'Edit sales order' : 'New sales order' }}
        </h3>

        <div class="overflow-y-auto pr-1 space-y-4">
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="mb-1 block text-sm font-medium">Customer</label>
              <select
                v-model="form.customer_id"
                required
                class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm"
              >
                <option value="">Select customer</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">
                  {{ c.name }}{{ c.company_name ? ` (${c.company_name})` : '' }}
                </option>
              </select>
              <p v-if="errors.customer_id" class="mt-1 text-sm text-red-600">
                {{ errors.customer_id[0] }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Order Date</label>
              <input
                v-model="form.order_date"
                type="date"
                class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
              />
              <p v-if="errors.order_date" class="mt-1 text-sm text-red-600">
                {{ errors.order_date[0] }}
              </p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Expected Ship Date</label>
              <input
                v-model="form.expected_ship_date"
                type="date"
                class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
              />
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium">Shipping Address</label>
            <input
              v-model="form.shipping_address"
              type="text"
              placeholder="Delivery destination address..."
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
            />
          </div>

          <div>
            <div class="mb-2 flex items-center justify-between">
              <label class="text-sm font-semibold">Line Items</label>
              <button
                type="button"
                @click="addLine"
                class="text-xs font-medium text-blue-600 hover:underline"
              >
                + Add item
              </button>
            </div>

            <p v-if="errors.items" class="mb-2 text-sm text-red-600">{{ errors.items[0] }}</p>

            <table class="w-full text-left text-sm">
              <thead class="border-b bg-gray-50 text-gray-600">
                <tr>
                  <th scope="col" class="px-2 py-1.5">Product</th>
                  <th scope="col" class="w-24 px-2 py-1.5">Qty</th>
                  <th scope="col" class="w-28 px-2 py-1.5">Unit price</th>
                  <th scope="col" class="w-28 px-2 py-1.5 text-right">Line total</th>
                  <th scope="col" class="w-12 px-2 py-1.5"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(line, idx) in form.items" :key="idx" class="border-b last:border-0">
                  <td class="px-2 py-1.5">
                    <select
                      v-model="line.product_id"
                      @change="onProductChange(line)"
                      required
                      class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-sm"
                    >
                      <option value="">Select product</option>
                      <option v-for="p in allProducts" :key="p.id" :value="p.id">
                        {{ p.name }} ({{ p.sku }}) — {{ money(p.sell_price) }}
                      </option>
                    </select>
                  </td>
                  <td class="px-2 py-1.5">
                    <input
                      v-model="line.quantity"
                      type="number"
                      min="1"
                      required
                      class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                    />
                  </td>
                  <td class="px-2 py-1.5">
                    <input
                      v-model="line.unit_price"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-full rounded border border-gray-300 px-2 py-1 text-sm"
                    />
                  </td>
                  <td class="px-2 py-1.5 text-right font-medium text-gray-700">
                    {{ money((Number(line.quantity) || 0) * (Number(line.unit_price) || 0)) }}
                  </td>
                  <td class="px-2 py-1.5 text-right">
                    <button
                      type="button"
                      @click="removeLine(idx)"
                      class="text-xs text-red-600 hover:underline"
                    >
                      ✕
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="grid grid-cols-2 gap-4 border-t pt-3">
            <div>
              <label class="mb-1 block text-sm font-medium">Notes</label>
              <textarea
                v-model="form.notes"
                rows="2"
                placeholder="Optional customer instructions..."
                class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
              ></textarea>
            </div>

            <div class="space-y-2 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Subtotal:</span>
                <span class="font-medium">{{ money(formSubtotal) }}</span>
              </div>
              <div class="flex items-center justify-between gap-3">
                <span class="text-gray-600">Discount ($):</span>
                <input
                  v-model="form.discount"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-24 rounded border border-gray-300 px-2 py-1 text-right text-sm"
                />
              </div>
              <div class="flex items-center justify-between gap-3">
                <span class="text-gray-600">Tax ($):</span>
                <input
                  v-model="form.tax"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-24 rounded border border-gray-300 px-2 py-1 text-right text-sm"
                />
              </div>
              <div class="flex items-center justify-between border-t pt-2 text-base font-semibold">
                <span>Grand Total:</span>
                <span class="text-blue-600">{{ money(formTotal) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 flex justify-end gap-2 border-t pt-3">
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

    <!-- Ship / Dispatch Modal -->
    <div
      v-if="showShip"
      role="dialog"
      aria-modal="true"
      aria-label="Ship sales order"
      class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-4"
    >
      <div class="flex max-h-[85vh] w-full max-w-2xl flex-col rounded-lg bg-white p-6 shadow-lg">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold">Ship Sales Order</h3>
            <p class="text-sm text-gray-500">
              {{ shipTarget?.number }} — {{ shipTarget?.customer?.name }}
            </p>
          </div>
          <button @click="showShip = false" class="rounded border px-3 py-1 text-sm">Close</button>
        </div>

        <div v-if="shipErrorList.length" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">
          <p v-for="(err, i) in shipErrorList" :key="i">{{ err }}</p>
        </div>

        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Source Warehouse</label>
          <select
            v-model="shipForm.warehouse_id"
            required
            class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm"
          >
            <option value="">Select fulfillment warehouse</option>
            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
          </select>
        </div>

        <div class="mb-2 flex items-center justify-between">
          <span class="text-sm font-semibold">Quantities to Dispatch</span>
          <button
            type="button"
            @click="shipAll"
            class="text-xs font-medium text-blue-600 hover:underline"
          >
            Ship all remaining
          </button>
        </div>

        <div class="overflow-y-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b bg-gray-50 text-gray-600">
              <tr>
                <th scope="col" class="px-3 py-2">Product</th>
                <th scope="col" class="px-3 py-2 text-right">Ordered</th>
                <th scope="col" class="px-3 py-2 text-right">Shipped</th>
                <th scope="col" class="px-3 py-2 text-right">Remaining</th>
                <th scope="col" class="w-28 px-3 py-2 text-right">Ship Now</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="line in shipLines" :key="line.id" class="border-b last:border-0">
                <td class="px-3 py-2">
                  <span class="font-medium">{{ line.product_name }}</span>
                  <span class="block font-mono text-xs text-gray-500">{{ line.product_sku }}</span>
                </td>
                <td class="px-3 py-2 text-right">{{ line.ordered }}</td>
                <td class="px-3 py-2 text-right text-gray-600">{{ line.shipped }}</td>
                <td class="px-3 py-2 text-right font-medium">{{ line.remaining }}</td>
                <td class="px-3 py-2 text-right">
                  <input
                    v-model="line.quantity"
                    type="number"
                    min="0"
                    :max="line.remaining"
                    class="w-20 rounded border border-gray-300 px-2 py-1 text-right text-sm"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-end gap-2 border-t pt-3">
          <button type="button" @click="showShip = false" class="rounded border px-4 py-2 text-sm">
            Cancel
          </button>
          <button
            :disabled="savingShip || !shipForm.warehouse_id"
            @click="submitShip"
            class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 disabled:opacity-50"
          >
            {{ savingShip ? 'Dispatching...' : 'Dispatch Stock' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
