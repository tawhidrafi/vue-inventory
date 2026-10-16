<script setup>
import { ref, onMounted } from 'vue'
import { stocksApi } from '@/api/stocks'

const summary = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await stocksApi.summary()
    summary.value = data
  } catch {
    error.value = 'Could not load summary.'
  }
})
</script>

<template>
  <div>
    <h2 class="mb-4 text-xl font-semibold">Dashboard</h2>

    <p v-if="error" class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p>

    <div v-if="summary" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
      <div class="rounded-lg bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Products</p>
        <p class="text-2xl font-semibold">{{ summary.products }}</p>
      </div>
      <div class="rounded-lg bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Categories</p>
        <p class="text-2xl font-semibold">{{ summary.categories }}</p>
      </div>
      <div class="rounded-lg bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Suppliers</p>
        <p class="text-2xl font-semibold">{{ summary.suppliers }}</p>
      </div>
      <div class="rounded-lg bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Warehouses</p>
        <p class="text-2xl font-semibold">{{ summary.warehouses }}</p>
      </div>
      <div class="rounded-lg bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Units in stock</p>
        <p class="text-2xl font-semibold">{{ summary.total_units }}</p>
      </div>
      <div class="rounded-lg bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Low stock</p>
        <p class="text-2xl font-semibold" :class="summary.low_stock > 0 ? 'text-red-600' : ''">
          {{ summary.low_stock }}
        </p>
      </div>
    </div>
  </div>
</template>
