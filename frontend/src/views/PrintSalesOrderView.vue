<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { salesOrdersApi } from '@/api/salesOrders'

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
    const { data } = await salesOrdersApi.show(route.params.id)
    order.value = data.data
    document.title = `${order.value.number} — Sales Order`
  } catch {
    error.value = 'Could not load the sales order.'
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
            <h1 class="text-2xl font-bold">Sales Order / Packing Slip</h1>
            <p class="font-mono text-sm text-gray-600">{{ order.number }}</p>
          </div>
          <div class="text-right text-sm">
            <p class="font-semibold capitalize text-blue-600">
              {{ order.status.replace('_', ' ') }}
            </p>
            <p class="text-gray-500">
              Ordered {{ order.order_date ? order.order_date.substring(0, 10) : '-' }}
            </p>
            <p v-if="order.expected_ship_date" class="text-gray-500">
              Expected Ship {{ order.expected_ship_date.substring(0, 10) }}
            </p>
            <p v-if="order.shipped_at" class="text-gray-500">
              Shipped {{ order.shipped_at.substring(0, 10) }}
            </p>
          </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-6">
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Customer</p>
            <p class="font-semibold text-gray-900">{{ order.customer?.name ?? '-' }}</p>
            <p v-if="order.customer?.company_name" class="text-sm font-medium text-gray-700">
              {{ order.customer.company_name }}
            </p>
            <p v-if="order.customer?.email" class="text-sm text-gray-600">
              {{ order.customer.email }}
            </p>
            <p v-if="order.customer?.phone" class="text-sm text-gray-600">
              {{ order.customer.phone }}
            </p>
            <p v-if="order.customer?.tax_number" class="text-xs text-gray-500">
              Tax ID: {{ order.customer.tax_number }}
            </p>
          </div>

          <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Shipping Destination</p>
            <p v-if="order.shipping_address" class="whitespace-pre-line text-sm text-gray-700">
              {{ order.shipping_address }}
            </p>
            <p
              v-else-if="order.customer?.address"
              class="whitespace-pre-line text-sm text-gray-700"
            >
              {{ order.customer.address }}
            </p>
            <p v-else class="text-sm text-gray-400">Same as billing address</p>
          </div>
        </div>

        <table class="mb-4 w-full text-left text-sm">
          <thead class="border-b-2 border-gray-300 text-gray-600">
            <tr>
              <th scope="col" class="py-2">Product</th>
              <th scope="col" class="py-2">SKU</th>
              <th scope="col" class="py-2 text-right">Ordered</th>
              <th scope="col" class="py-2 text-right">Shipped</th>
              <th scope="col" class="py-2 text-right">Unit Price</th>
              <th scope="col" class="py-2 text-right">Line Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in order.items" :key="item.id" class="border-b last:border-0">
              <td class="py-2">{{ item.product_name }}</td>
              <td class="py-2 font-mono text-xs text-gray-600">{{ item.product_sku }}</td>
              <td class="py-2 text-right">{{ item.quantity }}</td>
              <td class="py-2 text-right">{{ item.quantity_shipped }}</td>
              <td class="py-2 text-right">{{ money(item.unit_price) }}</td>
              <td class="py-2 text-right">{{ money(item.line_total) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="5" class="pt-3 text-right text-gray-600">Subtotal</td>
              <td class="pt-3 text-right font-medium">{{ money(order.subtotal) }}</td>
            </tr>
            <tr v-if="Number(order.discount) > 0">
              <td colspan="5" class="py-1 text-right text-gray-600">Discount</td>
              <td class="py-1 text-right text-red-600">-{{ money(order.discount) }}</td>
            </tr>
            <tr v-if="Number(order.tax) > 0">
              <td colspan="5" class="py-1 text-right text-gray-600">Tax</td>
              <td class="py-1 text-right text-gray-700">+{{ money(order.tax) }}</td>
            </tr>
            <tr class="border-t">
              <td colspan="5" class="pt-2 text-right font-bold text-gray-900">Total</td>
              <td class="pt-2 text-right font-bold text-gray-900">{{ money(order.total) }}</td>
            </tr>
          </tfoot>
        </table>

        <div v-if="order.notes" class="mt-6 border-t pt-4">
          <p class="text-xs uppercase tracking-wide text-gray-400">Notes & Instructions</p>
          <p class="whitespace-pre-line text-sm text-gray-700">{{ order.notes }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
