<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { purchaseOrdersApi } from '@/api/purchaseOrders'

const route = useRoute()
const router = useRouter()

const order = ref(null)
const error = ref('')

function money(value) {
  return `$${Number(value ?? 0).toFixed(2)}`
}

function printPage() {
  window.print()
}

onMounted(async () => {
  try {
    const { data } = await purchaseOrdersApi.show(route.params.id)
    order.value = data.data
    document.title = `${order.value.number} — Purchase order`
  } catch {
    error.value = 'Could not load the purchase order.'
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-100 p-4 print:bg-white print:p-0">
    <div class="mx-auto max-w-3xl">
      <div class="mb-4 flex items-center justify-between print:hidden">
        <button
          class="rounded border bg-white px-4 py-2 text-sm hover:bg-gray-50"
          @click="router.back()"
        >
          ← Back
        </button>
        <button
          v-if="order"
          class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
          @click="printPage"
        >
          Print / Save as PDF
        </button>
      </div>

      <p v-if="error" class="rounded bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>

      <div
        v-else-if="order"
        class="rounded-lg bg-white p-8 shadow print:rounded-none print:p-0 print:shadow-none"
      >
        <div class="mb-6 flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-bold">Purchase Order</h1>
            <p class="font-mono text-sm text-gray-600">{{ order.number }}</p>
          </div>
          <div class="text-right text-sm">
            <p class="font-medium capitalize">{{ order.status.replace('_', ' ') }}</p>
            <p class="text-gray-500">
              Issued {{ order.created_at ? order.created_at.substring(0, 10) : '-' }}
            </p>
            <p v-if="order.expected_at" class="text-gray-500">
              Expected {{ order.expected_at.substring(0, 10) }}
            </p>
            <p v-if="order.received_at" class="text-gray-500">
              Received {{ order.received_at.substring(0, 10) }}
            </p>
          </div>
        </div>

        <div class="mb-6">
          <p class="text-xs uppercase tracking-wide text-gray-400">Supplier</p>
          <p class="font-medium">{{ order.supplier?.name ?? '-' }}</p>
          <p v-if="order.supplier?.email" class="text-sm text-gray-600">
            {{ order.supplier.email }}
          </p>
          <p v-if="order.supplier?.phone" class="text-sm text-gray-600">
            {{ order.supplier.phone }}
          </p>
        </div>

        <table class="mb-4 w-full text-left text-sm">
          <thead class="border-b-2 border-gray-300 text-gray-600">
            <tr>
              <th scope="col" class="py-2">Product</th>
              <th scope="col" class="py-2">SKU</th>
              <th scope="col" class="py-2 text-right">Ordered</th>
              <th scope="col" class="py-2 text-right">Received</th>
              <th scope="col" class="py-2 text-right">Unit cost</th>
              <th scope="col" class="py-2 text-right">Line total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in order.items" :key="item.id" class="border-b last:border-0">
              <td class="py-2">{{ item.product_name }}</td>
              <td class="py-2 font-mono text-xs text-gray-600">{{ item.product_sku }}</td>
              <td class="py-2 text-right">{{ item.quantity }}</td>
              <td class="py-2 text-right">{{ item.quantity_received }}</td>
              <td class="py-2 text-right">{{ money(item.unit_cost) }}</td>
              <td class="py-2 text-right">{{ money(item.line_total) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="5" class="pt-3 text-right font-semibold">Total</td>
              <td class="pt-3 text-right font-semibold">{{ money(order.total) }}</td>
            </tr>
          </tfoot>
        </table>

        <div v-if="order.notes">
          <p class="text-xs uppercase tracking-wide text-gray-400">Notes</p>
          <p class="whitespace-pre-line text-sm text-gray-700">{{ order.notes }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
