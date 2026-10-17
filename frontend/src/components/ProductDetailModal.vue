<script setup>
import { ref, watch } from 'vue'
import { stocksApi } from '@/api/stocks'
import { stockMovementsApi } from '@/api/stockMovements'
import { productSuppliersApi } from '@/api/productSuppliers'

const props = defineProps({
  product: { type: Object, default: null },
})
const emit = defineEmits(['close'])

// image URLs are served by the backend origin, not the frontend one
const API_ORIGIN = (import.meta.env.VITE_API_URL || '').replace(/\/api\/?$/, '')

const loading = ref(false)
const error = ref('')
const detail = ref(null)
const movements = ref([])
const suppliers = ref([])

const movementStyles = {
  in: 'text-green-600',
  out: 'text-red-600',
  adjustment: 'text-amber-600',
  transfer_in: 'text-blue-600',
  transfer_out: 'text-blue-600',
}

function imageUrl(path) {
  return path ? `${API_ORIGIN}/storage/${path}` : null
}

function money(value) {
  return `$${Number(value ?? 0).toFixed(2)}`
}

function stripSku(sku) {
  return sku ? sku.replace(/::deleted::\d+$/, '') : sku
}

watch(
  () => props.product,
  async (product) => {
    if (!product) return

    loading.value = true
    error.value = ''
    detail.value = null
    movements.value = []
    suppliers.value = []

    try {
      const [stock, movementList, supplierList] = await Promise.all([
        stocksApi.forProduct(product.id),
        stockMovementsApi.list({ product_id: product.id, per_page: 10 }),
        productSuppliersApi.list(product.id),
      ])
      detail.value = stock.data
      movements.value = movementList.data.data
      suppliers.value = supplierList.data.data
    } catch {
      error.value = 'Could not load product details.'
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)
</script>

<template>
  <div
    role="dialog"
    aria-modal="true"
    aria-label="Product details"
    class="fixed inset-0 z-10 flex items-start justify-center overflow-y-auto bg-black/40 p-4"
    @click.self="emit('close')"
  >
    <div class="my-8 w-full max-w-3xl space-y-5 rounded-lg bg-white p-6 shadow-lg">
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-4">
          <img
            v-if="imageUrl(product?.image)"
            :src="imageUrl(product.image)"
            class="h-16 w-16 rounded object-cover"
            alt=""
          />
          <div>
            <h3 class="text-lg font-semibold">{{ product?.name }}</h3>
            <p class="font-mono text-xs text-gray-500">{{ stripSku(product?.sku) }}</p>
          </div>
        </div>
        <button type="button" class="text-gray-400 hover:text-gray-700" @click="emit('close')">
          ✕
        </button>
      </div>

      <p v-if="error" class="rounded bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>
      <p v-else-if="loading" class="text-sm text-gray-500">Loading...</p>

      <template v-else-if="detail">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
          <div class="rounded bg-gray-50 p-3">
            <p class="text-xs text-gray-500">On hand</p>
            <p class="text-lg font-semibold">{{ detail.total }}</p>
          </div>
          <div class="rounded bg-gray-50 p-3">
            <p class="text-xs text-gray-500">Reorder level</p>
            <p class="text-lg font-semibold">{{ detail.reorder_level }}</p>
          </div>
          <div class="rounded bg-gray-50 p-3">
            <p class="text-xs text-gray-500">Cost price</p>
            <p class="text-lg font-semibold">{{ money(detail.product?.cost_price) }}</p>
          </div>
          <div class="rounded bg-gray-50 p-3">
            <p class="text-xs text-gray-500">Sell price</p>
            <p class="text-lg font-semibold">{{ money(detail.product?.sell_price) }}</p>
          </div>
        </div>

        <div>
          <h4 class="mb-2 font-medium">Stock by warehouse</h4>
          <div class="overflow-hidden rounded border">
            <table class="w-full text-left text-sm">
              <thead class="border-b bg-gray-50 text-gray-600">
                <tr>
                  <th scope="col" class="px-3 py-2">Warehouse</th>
                  <th scope="col" class="px-3 py-2 text-right">Quantity</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!detail.stocks?.length">
                  <td colspan="2" class="px-3 py-4 text-center text-gray-500">
                    No stock recorded.
                  </td>
                </tr>
                <tr v-for="s in detail.stocks" :key="s.id" class="border-b last:border-0">
                  <td class="px-3 py-2">{{ s.warehouse?.name ?? '-' }}</td>
                  <td class="px-3 py-2 text-right">{{ s.quantity }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <h4 class="mb-2 font-medium">Suppliers</h4>
          <div class="overflow-hidden rounded border">
            <table class="w-full text-left text-sm">
              <thead class="border-b bg-gray-50 text-gray-600">
                <tr>
                  <th scope="col" class="px-3 py-2">Supplier</th>
                  <th scope="col" class="px-3 py-2">Supplier SKU</th>
                  <th scope="col" class="px-3 py-2 text-right">Unit cost</th>
                  <th scope="col" class="px-3 py-2 text-right">Lead time</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!suppliers.length">
                  <td colspan="4" class="px-3 py-4 text-center text-gray-500">
                    Not linked to any supplier.
                  </td>
                </tr>
                <tr v-for="s in suppliers" :key="s.id" class="border-b last:border-0">
                  <td class="px-3 py-2">
                    {{ s.name }}
                    <span
                      v-if="s.is_preferred"
                      class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700"
                      >Preferred</span
                    >
                  </td>
                  <td class="px-3 py-2 font-mono text-xs text-gray-600">
                    {{ s.supplier_sku ?? '-' }}
                  </td>
                  <td class="px-3 py-2 text-right">
                    {{ s.unit_cost == null ? '-' : money(s.unit_cost) }}
                  </td>
                  <td class="px-3 py-2 text-right">
                    {{ s.lead_time_days == null ? '-' : `${s.lead_time_days} days` }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <h4 class="mb-2 font-medium">Recent movements</h4>
          <div class="overflow-hidden rounded border">
            <table class="w-full text-left text-sm">
              <thead class="border-b bg-gray-50 text-gray-600">
                <tr>
                  <th scope="col" class="px-3 py-2">Date</th>
                  <th scope="col" class="px-3 py-2">Type</th>
                  <th scope="col" class="px-3 py-2">Warehouse</th>
                  <th scope="col" class="px-3 py-2 text-right">Change</th>
                  <th scope="col" class="px-3 py-2 text-right">Balance</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!movements.length">
                  <td colspan="5" class="px-3 py-4 text-center text-gray-500">No movements yet.</td>
                </tr>
                <tr v-for="m in movements" :key="m.id" class="border-b last:border-0">
                  <td class="px-3 py-2 text-gray-600">
                    {{ m.created_at ? m.created_at.substring(0, 10) : '-' }}
                  </td>
                  <td class="px-3 py-2 capitalize" :class="movementStyles[m.type]">
                    {{ m.type.replace('_', ' ') }}
                  </td>
                  <td class="px-3 py-2 text-gray-600">{{ m.warehouse_name ?? '-' }}</td>
                  <td class="px-3 py-2 text-right" :class="movementStyles[m.type]">
                    {{ m.quantity > 0 ? '+' : '' }}{{ m.quantity }}
                  </td>
                  <td class="px-3 py-2 text-right">{{ m.balance_after }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
