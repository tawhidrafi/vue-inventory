<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}

const links = [
  { to: '/', label: 'Dashboard' },
  { to: '/categories', label: 'Categories' },
  { to: '/products', label: 'Products' },
  { to: '/stock', label: 'Stock' },
  { to: '/reports', label: 'Reports' },
  { to: '/purchase-orders', label: 'Purchase Orders' },
  { to: '/suppliers', label: 'Suppliers' },
  { to: '/warehouses', label: 'Warehouses' },
]
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <header class="flex items-center justify-between bg-white px-6 py-4 shadow">
      <div class="flex items-center gap-8">
        <h1 class="text-lg font-semibold">Inventory</h1>
        <nav class="flex gap-4 text-sm">
          <RouterLink
            v-for="l in links"
            :key="l.to"
            :to="l.to"
            class="text-gray-600 hover:text-gray-900"
            exact-active-class="font-semibold !text-blue-600"
          >
            {{ l.label }}
          </RouterLink>
        </nav>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600">{{ auth.user?.name }}</span>
        <button @click="logout" class="rounded bg-gray-800 px-3 py-1.5 text-sm text-white hover:bg-gray-700">
          Logout
        </button>
      </div>
    </header>

    <main class="p-6">
      <RouterView />
    </main>
  </div>
</template>