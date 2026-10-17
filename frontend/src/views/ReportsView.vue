<script setup>
import { ref, onMounted } from 'vue'
import { stocksApi } from '@/api/stocks'

const report = ref(null)
const error = ref('')

function money(value) {
  return `$${Number(value ?? 0).toFixed(2)}`
}

onMounted(async () => {
  try {
    const { data } = await stocksApi.valuation()
    report.value = data
  } catch {
    error.value = 'Could not load the valuation report.'
  }
})
</script>

<template>
  <div>
    <h2 class="mb-4 text-xl font-semibold">Inventory valuation</h2>

    <p v-if="error" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>

    <template v-if="report">
      <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-sm text-gray-500">Units in stock</p>
          <p class="text-2xl font-semibold">{{ report.total_units }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-sm text-gray-500">Value at cost</p>
          <p class="text-2xl font-semibold">{{ money(report.cost_value) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-sm text-gray-500">Value at retail</p>
          <p class="text-2xl font-semibold">{{ money(report.retail_value) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-sm text-gray-500">Potential margin</p>
          <p class="text-2xl font-semibold text-green-600">{{ money(report.potential_margin) }}</p>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white shadow">
          <h3 class="border-b px-4 py-3 font-semibold">By warehouse</h3>
          <table class="w-full text-left text-sm">
            <thead class="border-b bg-gray-50 text-gray-600">
              <tr>
                <th scope="col" class="px-4 py-2">Warehouse</th>
                <th scope="col" class="px-4 py-2 text-right">Units</th>
                <th scope="col" class="px-4 py-2 text-right">Cost</th>
                <th scope="col" class="px-4 py-2 text-right">Retail</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!report.by_warehouse.length">
                <td colspan="4" class="px-4 py-6 text-center text-gray-500">No stock.</td>
              </tr>
              <tr
                v-for="row in report.by_warehouse"
                :key="row.warehouse_id"
                class="border-b last:border-0"
              >
                <td class="px-4 py-2">{{ row.warehouse_name ?? '-' }}</td>
                <td class="px-4 py-2 text-right">{{ row.units }}</td>
                <td class="px-4 py-2 text-right">{{ money(row.cost_value) }}</td>
                <td class="px-4 py-2 text-right">{{ money(row.retail_value) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="rounded-lg bg-white shadow">
          <h3 class="border-b px-4 py-3 font-semibold">By category</h3>
          <table class="w-full text-left text-sm">
            <thead class="border-b bg-gray-50 text-gray-600">
              <tr>
                <th scope="col" class="px-4 py-2">Category</th>
                <th scope="col" class="px-4 py-2 text-right">Units</th>
                <th scope="col" class="px-4 py-2 text-right">Cost</th>
                <th scope="col" class="px-4 py-2 text-right">Retail</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!report.by_category.length">
                <td colspan="4" class="px-4 py-6 text-center text-gray-500">No stock.</td>
              </tr>
              <tr
                v-for="row in report.by_category"
                :key="row.category_id ?? 'none'"
                class="border-b last:border-0"
              >
                <td class="px-4 py-2">{{ row.category_name }}</td>
                <td class="px-4 py-2 text-right">{{ row.units }}</td>
                <td class="px-4 py-2 text-right">{{ money(row.cost_value) }}</td>
                <td class="px-4 py-2 text-right">{{ money(row.retail_value) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <p v-else-if="!error" class="text-gray-500">Loading...</p>
  </div>
</template>
