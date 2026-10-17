<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

// Mobile drawer state
const isMobileOpen = ref(false)

async function logout() {
  isMobileOpen.value = false
  await auth.logout()
  router.push({ name: 'login' })
}

// User initials computation (e.g. "AD" for "Admin User" or "A" for "Admin")
const userInitials = computed(() => {
  const name = auth.user?.name || ''
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase() || 'U'
})

// Role badge styling
const roleBadgeClass = computed(() => {
  switch (auth.user?.role) {
    case 'admin':
      return 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20'
    case 'manager':
      return 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20'
    case 'staff':
      return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'
    default:
      return 'bg-gray-100 text-gray-700 ring-1 ring-gray-600/20'
  }
})

// Active route check helper
function isRouteActive(path, exact = false) {
  if (exact) {
    return route.path === path
  }
  return route.path.startsWith(path)
}

// Permissions
const canAccessActivity = computed(() => ['admin', 'manager'].includes(auth.user?.role))
const canAccessUsers = computed(() => auth.user?.role === 'admin')
const hasAdminSection = computed(() => canAccessActivity.value || canAccessUsers.value)

// Keyboard shortcuts (ESC to close mobile menu)
function handleKeydown(e) {
  if (e.key === 'Escape') {
    isMobileOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})

// Close mobile drawer whenever route changes
watch(
  () => route.fullPath,
  () => {
    isMobileOpen.value = false
  },
)
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
    <!-- ============================================================ -->
    <!-- DESKTOP FIXED SIDEBAR (lg and up)                            -->
    <!-- ============================================================ -->
    <aside
      class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 z-30 bg-white border-r border-gray-200/90 shadow-2xs"
    >
      <!-- Sidebar Header / Brand Logo -->
      <div class="h-16 flex items-center px-6 border-b border-gray-100">
        <RouterLink
          to="/"
          class="group flex items-center gap-3 focus-visible:outline-blue-600 rounded-lg"
          aria-label="Go to Dashboard"
        >
          <div
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-sm shadow-blue-500/25 transition-transform duration-200 group-hover:scale-105"
          >
            <!-- Isometric Package SVG -->
            <svg
              class="h-5 w-5"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path
                d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"
              />
              <path d="m3.3 7 8.7 5 8.7-5" />
              <path d="M12 22V12" />
            </svg>
          </div>
          <div class="flex flex-col">
            <span
              class="text-base font-bold tracking-tight text-gray-900 group-hover:text-blue-600 transition-colors leading-tight"
            >
              Inventory
            </span>
            <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
              Operations Hub
            </span>
          </div>
        </RouterLink>
      </div>

      <!-- Quick Search / Scan Shortcut -->
      <div class="px-4 pt-4 pb-2">
        <RouterLink
          to="/products"
          class="flex items-center justify-between w-full rounded-lg border border-gray-200/90 bg-gray-50/70 px-3 py-2 text-xs text-gray-500 hover:border-gray-300 hover:bg-gray-100 transition-colors"
          title="Quickly search or scan inventory items"
        >
          <div class="flex items-center gap-2">
            <svg
              class="h-4 w-4 text-gray-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
            <span>Scan / Search SKU</span>
          </div>
          <kbd
            class="rounded border border-gray-200 bg-white px-1.5 py-0.5 text-[10px] font-mono text-gray-400 shadow-2xs"
          >
            /
          </kbd>
        </RouterLink>
      </div>

      <!-- Scrollable Navigation Items -->
      <nav class="flex-1 px-4 py-2 space-y-6 overflow-y-auto">
        <!-- Section: Overview -->
        <div>
          <p class="px-2 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400">
            Overview
          </p>
          <div class="space-y-0.5">
            <RouterLink
              to="/"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/', true)
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                />
              </svg>
              Dashboard
            </RouterLink>

            <RouterLink
              to="/reports"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/reports')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
                />
              </svg>
              Reports & Valuation
            </RouterLink>
          </div>
        </div>

        <!-- Section: Operations -->
        <div>
          <p class="px-2 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400">
            Operations
          </p>
          <div class="space-y-0.5">
            <RouterLink
              to="/products"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/products')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                />
              </svg>
              Products Catalog
            </RouterLink>

            <RouterLink
              to="/stock"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/stock')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                />
              </svg>
              Stock & Movements
            </RouterLink>

            <RouterLink
              to="/sales-orders"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/sales-orders')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                />
              </svg>
              Sales Orders
            </RouterLink>

            <RouterLink
              to="/purchase-orders"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/purchase-orders')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                />
              </svg>
              Purchase Orders
            </RouterLink>
          </div>
        </div>

        <!-- Section: Directories & Master Data -->
        <div>
          <p class="px-2 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400">
            Master Data
          </p>
          <div class="space-y-0.5">
            <RouterLink
              to="/customers"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/customers')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>
              Customers
            </RouterLink>

            <RouterLink
              to="/suppliers"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/suppliers')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                />
              </svg>
              Suppliers
            </RouterLink>

            <RouterLink
              to="/categories"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/categories')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                />
              </svg>
              Categories
            </RouterLink>

            <RouterLink
              to="/warehouses"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/warehouses')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"
                />
              </svg>
              Warehouses
            </RouterLink>
          </div>
        </div>

        <!-- Section: System & Administration -->
        <div v-if="hasAdminSection">
          <p class="px-2 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400">
            System
          </p>
          <div class="space-y-0.5">
            <RouterLink
              v-if="canAccessActivity"
              to="/activity"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/activity')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              Activity Log
            </RouterLink>

            <RouterLink
              v-if="canAccessUsers"
              to="/users"
              class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="
                isRouteActive('/users')
                  ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10 shadow-xs'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
              "
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
              User Management
            </RouterLink>
          </div>
        </div>
      </nav>

      <!-- Sidebar Footer / Current User Profile Card -->
      <div class="p-3 border-t border-gray-100 bg-gray-50/60">
        <div class="flex items-center justify-between gap-2 p-1.5 rounded-lg">
          <div class="flex items-center gap-2.5 min-w-0">
            <div
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-semibold text-xs shadow-xs"
            >
              {{ userInitials }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xs font-semibold text-gray-900 truncate">
                {{ auth.user?.name }}
              </p>
              <span
                class="inline-block rounded-full px-2 py-0.2 text-[10px] font-medium capitalize mt-0.5"
                :class="roleBadgeClass"
              >
                {{ auth.role || 'User' }}
              </span>
            </div>
          </div>

          <!-- Quick Action Buttons: Settings & Sign Out -->
          <div class="flex items-center gap-1 shrink-0">
            <RouterLink
              to="/settings"
              class="p-1.5 text-gray-500 hover:text-gray-800 hover:bg-gray-200/70 rounded-md transition-colors"
              title="Preferences & Settings"
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </RouterLink>

            <button
              type="button"
              @click="logout"
              class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
              title="Sign Out"
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </aside>

    <!-- ============================================================ -->
    <!-- MOBILE HEADER & DRAWER (< lg)                                -->
    <!-- ============================================================ -->
    <header
      class="lg:hidden sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white/95 px-4 shadow-xs backdrop-blur-sm"
    >
      <div class="flex items-center gap-3">
        <button
          type="button"
          @click="isMobileOpen = true"
          class="flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors focus-visible:outline-blue-600"
          aria-label="Open sidebar navigation"
          :aria-expanded="isMobileOpen"
        >
          <svg
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <RouterLink to="/" class="flex items-center gap-2">
          <div
            class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-xs"
          >
            <svg
              class="h-4 w-4"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"
              />
              <path d="m3.3 7 8.7 5 8.7-5" />
              <path d="M12 22V12" />
            </svg>
          </div>
          <span class="font-bold text-gray-900">Inventory</span>
        </RouterLink>
      </div>

      <div class="flex items-center gap-2">
        <RouterLink
          to="/products"
          class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
          title="Scan or search products"
        >
          <svg
            class="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </RouterLink>

        <div
          class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-semibold text-xs"
        >
          {{ userInitials }}
        </div>
      </div>
    </header>

    <!-- Mobile Drawer Overlay & Menu -->
    <div v-if="isMobileOpen" class="relative z-50 lg:hidden">
      <!-- Backdrop -->
      <div
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity duration-300"
        @click="isMobileOpen = false"
      ></div>

      <!-- Off-canvas panel -->
      <div
        class="fixed inset-y-0 left-0 w-72 max-w-[85vw] bg-white shadow-2xl flex flex-col z-50 animate-in slide-in-from-left duration-200"
      >
        <!-- Drawer Header -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-gray-100">
          <RouterLink to="/" class="flex items-center gap-3" @click="isMobileOpen = false">
            <div
              class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white"
            >
              <svg
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"
                />
                <path d="m3.3 7 8.7 5 8.7-5" />
                <path d="M12 22V12" />
              </svg>
            </div>
            <div class="flex flex-col">
              <span class="text-base font-bold text-gray-900 leading-tight"> Inventory </span>
              <span class="text-[10px] font-semibold uppercase text-gray-400">
                Operations Hub
              </span>
            </div>
          </RouterLink>

          <button
            type="button"
            @click="isMobileOpen = false"
            class="p-2 text-gray-500 hover:text-gray-900 rounded-lg hover:bg-gray-100"
            aria-label="Close navigation"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Drawer Navigation Items -->
        <nav class="flex-1 px-4 py-3 space-y-6 overflow-y-auto">
          <!-- Overview -->
          <div>
            <p class="px-2 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400">
              Overview
            </p>
            <div class="space-y-0.5">
              <RouterLink
                to="/"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/', true)
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                  />
                </svg>
                Dashboard
              </RouterLink>

              <RouterLink
                to="/reports"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/reports')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
                  />
                </svg>
                Reports & Valuation
              </RouterLink>
            </div>
          </div>

          <!-- Operations -->
          <div>
            <p class="px-2 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400">
              Operations
            </p>
            <div class="space-y-0.5">
              <RouterLink
                to="/products"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/products')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                  />
                </svg>
                Products
              </RouterLink>

              <RouterLink
                to="/stock"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/stock')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                  />
                </svg>
                Stock & Movements
              </RouterLink>

              <RouterLink
                to="/sales-orders"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/sales-orders')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                  />
                </svg>
                Sales Orders
              </RouterLink>

              <RouterLink
                to="/purchase-orders"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/purchase-orders')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                  />
                </svg>
                Purchase Orders
              </RouterLink>
            </div>
          </div>

          <!-- Master Data -->
          <div>
            <p class="px-2 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400">
              Master Data
            </p>
            <div class="space-y-0.5">
              <RouterLink
                to="/customers"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/customers')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                  />
                </svg>
                Customers
              </RouterLink>

              <RouterLink
                to="/suppliers"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/suppliers')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                  />
                </svg>
                Suppliers
              </RouterLink>

              <RouterLink
                to="/categories"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/categories')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                  />
                </svg>
                Categories
              </RouterLink>

              <RouterLink
                to="/warehouses"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/warehouses')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"
                  />
                </svg>
                Warehouses
              </RouterLink>
            </div>
          </div>

          <!-- Administration -->
          <div v-if="hasAdminSection">
            <p class="px-2 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400">
              System
            </p>
            <div class="space-y-0.5">
              <RouterLink
                v-if="canAccessActivity"
                to="/activity"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/activity')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                Activity Log
              </RouterLink>

              <RouterLink
                v-if="canAccessUsers"
                to="/users"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg"
                :class="
                  isRouteActive('/users')
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-600 hover:bg-gray-100'
                "
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                  />
                </svg>
                User Management
              </RouterLink>
            </div>
          </div>
        </nav>

        <!-- Drawer Footer -->
        <div class="p-4 border-t border-gray-100 bg-gray-50/70">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-semibold text-xs shadow-xs"
              >
                {{ userInitials }}
              </div>
              <div>
                <p class="text-xs font-semibold text-gray-900 leading-tight">
                  {{ auth.user?.name }}
                </p>
                <span
                  class="inline-block rounded-full px-2 py-0.2 text-[10px] font-medium capitalize mt-0.5"
                  :class="roleBadgeClass"
                >
                  {{ auth.role }}
                </span>
              </div>
            </div>

            <RouterLink
              to="/settings"
              class="p-2 text-gray-500 hover:text-gray-800 rounded-lg hover:bg-gray-200/70"
              title="Settings"
            >
              <svg
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </RouterLink>
          </div>

          <button
            type="button"
            @click="logout"
            class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg bg-gray-200/80 py-2 text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"
          >
            <svg
              class="h-4 w-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
            Sign Out
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- MAIN CONTENT VIEWPORT (with left padding on lg+)             -->
    <!-- ============================================================ -->
    <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
      <main class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <RouterView />
      </main>
    </div>
  </div>
</template>
