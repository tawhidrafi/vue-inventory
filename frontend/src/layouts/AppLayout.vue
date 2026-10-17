<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

// Dropdown and mobile menu states
const isManageOpen = ref(false)
const isUserMenuOpen = ref(false)
const isMobileMenuOpen = ref(false)

const manageMenuRef = ref(null)
const userMenuRef = ref(null)

async function logout() {
  isUserMenuOpen.value = false
  isMobileMenuOpen.value = false
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

// Role styling badge
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

// Active check helper
function isRouteActive(path, exact = false) {
  if (exact) {
    return route.path === path
  }
  return route.path.startsWith(path)
}

// Check if any link inside the "Manage" dropdown is currently active
const isManageActive = computed(() => {
  return ['/customers', '/suppliers', '/categories', '/warehouses', '/activity', '/users'].some(
    (path) => route.path.startsWith(path),
  )
})

// Check if user has permission for administrative items
const canAccessActivity = computed(() => ['admin', 'manager'].includes(auth.user?.role))
const canAccessUsers = computed(() => auth.user?.role === 'admin')
const hasAnyAdminLink = computed(() => canAccessActivity.value || canAccessUsers.value)

// Close all menus on outside click
function handleDocumentClick(e) {
  if (manageMenuRef.value && !manageMenuRef.value.contains(e.target)) {
    isManageOpen.value = false
  }
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    isUserMenuOpen.value = false
  }
}

// Keyboard shortcuts / ESC key support
function handleKeydown(e) {
  if (e.key === 'Escape') {
    isManageOpen.value = false
    isUserMenuOpen.value = false
    isMobileMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('click', handleDocumentClick)
  document.removeEventListener('keydown', handleKeydown)
})

// Automatically close menus whenever route changes
watch(
  () => route.fullPath,
  () => {
    isManageOpen.value = false
    isUserMenuOpen.value = false
    isMobileMenuOpen.value = false
  },
)
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
    <!-- Top Navigation Header -->
    <header
      class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border-b border-gray-200/80 shadow-xs"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
          <!-- Brand Logo & Name -->
          <div class="flex items-center gap-6">
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
                  class="text-base font-bold tracking-tight text-gray-900 group-hover:text-blue-600 transition-colors"
                >
                  Inventory
                </span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                  Operations Hub
                </span>
              </div>
            </RouterLink>

            <!-- Desktop Nav Items -->
            <nav class="hidden lg:flex items-center gap-1">
              <!-- Dashboard -->
              <RouterLink
                to="/"
                class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                :class="
                  isRouteActive('/', true)
                    ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10'
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

              <!-- Products -->
              <RouterLink
                to="/products"
                class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                :class="
                  isRouteActive('/products')
                    ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10'
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
                Products
              </RouterLink>

              <!-- Stock -->
              <RouterLink
                to="/stock"
                class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                :class="
                  isRouteActive('/stock')
                    ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10'
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
                Stock
              </RouterLink>

              <!-- Sales Orders -->
              <RouterLink
                to="/sales-orders"
                class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                :class="
                  isRouteActive('/sales-orders')
                    ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10'
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

              <!-- Purchase Orders -->
              <RouterLink
                to="/purchase-orders"
                class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                :class="
                  isRouteActive('/purchase-orders')
                    ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10'
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

              <!-- Reports -->
              <RouterLink
                to="/reports"
                class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                :class="
                  isRouteActive('/reports')
                    ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10'
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
                Reports
              </RouterLink>

              <!-- Manage Dropdown (Master Data + Administration) -->
              <div ref="manageMenuRef" class="relative">
                <button
                  type="button"
                  @click="isManageOpen = !isManageOpen"
                  class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                  :class="
                    isManageActive || isManageOpen
                      ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-700/10'
                      : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'
                  "
                  :aria-expanded="isManageOpen"
                  aria-haspopup="true"
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
                      d="M4 6h16M4 12h16M4 18h7"
                    />
                  </svg>
                  Manage
                  <svg
                    class="h-3.5 w-3.5 transition-transform duration-200"
                    :class="{ 'rotate-180': isManageOpen }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <!-- Dropdown Menu Popover -->
                <div
                  v-if="isManageOpen"
                  class="absolute left-0 mt-2 w-72 origin-top-left rounded-xl bg-white shadow-xl ring-1 ring-black/5 border border-gray-100 p-2 z-40 transition ease-out duration-150 animate-in fade-in slide-in-from-top-1"
                >
                  <!-- Section: Master Data -->
                  <div
                    class="px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400"
                  >
                    Master Data & Directories
                  </div>

                  <RouterLink
                    to="/customers"
                    class="group flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm transition-colors"
                    :class="
                      isRouteActive('/customers')
                        ? 'bg-blue-50 text-blue-700 font-medium'
                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                    "
                  >
                    <div
                      class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100"
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
                    </div>
                    <div>
                      <p class="font-medium leading-none">Customers</p>
                      <p class="text-xs text-gray-500 mt-0.5">Clients, shipping & billing</p>
                    </div>
                  </RouterLink>

                  <RouterLink
                    to="/suppliers"
                    class="group flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm transition-colors"
                    :class="
                      isRouteActive('/suppliers')
                        ? 'bg-blue-50 text-blue-700 font-medium'
                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                    "
                  >
                    <div
                      class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-100"
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
                    </div>
                    <div>
                      <p class="font-medium leading-none">Suppliers</p>
                      <p class="text-xs text-gray-500 mt-0.5">Vendors & product sourcing</p>
                    </div>
                  </RouterLink>

                  <RouterLink
                    to="/categories"
                    class="group flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm transition-colors"
                    :class="
                      isRouteActive('/categories')
                        ? 'bg-blue-50 text-blue-700 font-medium'
                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                    "
                  >
                    <div
                      class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100"
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
                    </div>
                    <div>
                      <p class="font-medium leading-none">Categories</p>
                      <p class="text-xs text-gray-500 mt-0.5">Product classifications</p>
                    </div>
                  </RouterLink>

                  <RouterLink
                    to="/warehouses"
                    class="group flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm transition-colors"
                    :class="
                      isRouteActive('/warehouses')
                        ? 'bg-blue-50 text-blue-700 font-medium'
                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                    "
                  >
                    <div
                      class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600 group-hover:bg-sky-100"
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
                    </div>
                    <div>
                      <p class="font-medium leading-none">Warehouses</p>
                      <p class="text-xs text-gray-500 mt-0.5">Storage facilities & locations</p>
                    </div>
                  </RouterLink>

                  <!-- Section: Administration (if permitted) -->
                  <template v-if="hasAnyAdminLink">
                    <div class="my-1.5 border-t border-gray-100"></div>
                    <div
                      class="px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400"
                    >
                      System & Administration
                    </div>

                    <RouterLink
                      v-if="canAccessActivity"
                      to="/activity"
                      class="group flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm transition-colors"
                      :class="
                        isRouteActive('/activity')
                          ? 'bg-blue-50 text-blue-700 font-medium'
                          : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                      "
                    >
                      <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-50 text-orange-600 group-hover:bg-orange-100"
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
                      </div>
                      <div>
                        <p class="font-medium leading-none">Activity Log</p>
                        <p class="text-xs text-gray-500 mt-0.5">Audit trail & history</p>
                      </div>
                    </RouterLink>

                    <RouterLink
                      v-if="canAccessUsers"
                      to="/users"
                      class="group flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm transition-colors"
                      :class="
                        isRouteActive('/users')
                          ? 'bg-blue-50 text-blue-700 font-medium'
                          : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                      "
                    >
                      <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600 group-hover:bg-purple-100"
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
                      </div>
                      <div>
                        <p class="font-medium leading-none">User Accounts</p>
                        <p class="text-xs text-gray-500 mt-0.5">Staff credentials & roles</p>
                      </div>
                    </RouterLink>
                  </template>
                </div>
              </div>
            </nav>
          </div>

          <!-- Right: Quick Actions & User Menu -->
          <div class="flex items-center gap-3">
            <!-- Quick Barcode Scan shortcut / prompt -->
            <RouterLink
              to="/products"
              class="hidden sm:flex items-center gap-2 rounded-lg border border-gray-200/90 bg-gray-50/70 px-2.5 py-1.5 text-xs text-gray-500 hover:border-gray-300 hover:bg-gray-100/80 transition-colors"
              title="Quickly search or scan inventory items"
            >
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
                  d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
                />
              </svg>
              <span>Scan / Search</span>
              <kbd
                class="rounded border border-gray-200 bg-white px-1.5 py-0.5 text-[10px] font-mono text-gray-400 shadow-2xs"
              >
                SKU
              </kbd>
            </RouterLink>

            <!-- User Menu Dropdown -->
            <div ref="userMenuRef" class="relative">
              <button
                type="button"
                @click="isUserMenuOpen = !isUserMenuOpen"
                class="flex items-center gap-2.5 rounded-full p-1 sm:px-3 sm:py-1.5 text-left text-sm hover:bg-gray-100 transition-colors focus-visible:outline-blue-600"
                :aria-expanded="isUserMenuOpen"
                aria-haspopup="true"
              >
                <!-- Avatar with Initials -->
                <div
                  class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-semibold text-xs shadow-xs"
                >
                  {{ userInitials }}
                </div>

                <!-- User name & Role badge (desktop) -->
                <div class="hidden sm:flex flex-col text-left">
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-gray-800 leading-tight">
                      {{ auth.user?.name }}
                    </span>
                    <span
                      v-if="auth.role"
                      class="rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
                      :class="roleBadgeClass"
                    >
                      {{ auth.role }}
                    </span>
                  </div>
                </div>

                <svg
                  class="hidden sm:block h-3.5 w-3.5 text-gray-400 transition-transform duration-200"
                  :class="{ 'rotate-180': isUserMenuOpen }"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- User Menu Popover -->
              <div
                v-if="isUserMenuOpen"
                class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl bg-white shadow-xl ring-1 ring-black/5 border border-gray-100 p-1.5 z-40 transition ease-out duration-150 animate-in fade-in slide-in-from-top-1"
              >
                <!-- Profile Header -->
                <div class="px-3 py-2 border-b border-gray-100">
                  <p class="text-sm font-semibold text-gray-900 leading-tight">
                    {{ auth.user?.name }}
                  </p>
                  <p class="text-xs text-gray-500 truncate mt-0.5">
                    {{ auth.user?.email }}
                  </p>
                  <div class="mt-2">
                    <span
                      class="inline-block rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
                      :class="roleBadgeClass"
                    >
                      Role: {{ auth.role || 'User' }}
                    </span>
                  </div>
                </div>

                <!-- Settings Link -->
                <div class="py-1">
                  <RouterLink
                    to="/settings"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                  >
                    <svg
                      class="h-4 w-4 text-gray-500"
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
                    Settings & Preferences
                  </RouterLink>
                </div>

                <!-- Sign Out -->
                <div class="border-t border-gray-100 pt-1">
                  <button
                    type="button"
                    @click="logout"
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"
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

            <!-- Mobile Hamburger Toggle -->
            <button
              type="button"
              @click="isMobileMenuOpen = !isMobileMenuOpen"
              class="lg:hidden flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors focus-visible:outline-blue-600"
              aria-label="Toggle navigation menu"
              :aria-expanded="isMobileMenuOpen"
            >
              <svg
                v-if="!isMobileMenuOpen"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg
                v-else
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Navigation Drawer / Dropdown -->
      <div
        v-if="isMobileMenuOpen"
        class="lg:hidden border-t border-gray-200 bg-white px-4 py-4 space-y-4 max-h-[calc(100vh-4rem)] overflow-y-auto shadow-lg"
      >
        <!-- Primary Workflows -->
        <div>
          <p class="px-2 pb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">
            Workflows
          </p>
          <div class="grid grid-cols-2 gap-1.5">
            <RouterLink
              to="/"
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/', true)
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
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
              to="/products"
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/products')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
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
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/stock')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
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
              Stock
            </RouterLink>

            <RouterLink
              to="/sales-orders"
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/sales-orders')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
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
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/purchase-orders')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
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

            <RouterLink
              to="/reports"
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/reports')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
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
              Reports
            </RouterLink>
          </div>
        </div>

        <!-- Master Data -->
        <div class="border-t border-gray-100 pt-3">
          <p class="px-2 pb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">
            Master Data & Directories
          </p>
          <div class="grid grid-cols-2 gap-1.5">
            <RouterLink
              to="/customers"
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/customers')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <svg
                class="h-4 w-4 text-indigo-500"
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
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/suppliers')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <svg
                class="h-4 w-4 text-amber-500"
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
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/categories')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <svg
                class="h-4 w-4 text-emerald-500"
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
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/warehouses')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <svg
                class="h-4 w-4 text-sky-500"
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
        <div v-if="hasAnyAdminLink" class="border-t border-gray-100 pt-3">
          <p class="px-2 pb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">
            Administration
          </p>
          <div class="grid grid-cols-2 gap-1.5">
            <RouterLink
              v-if="canAccessActivity"
              to="/activity"
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/activity')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <svg
                class="h-4 w-4 text-orange-500"
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
              class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isRouteActive('/users')
                  ? 'bg-blue-50 text-blue-700 font-semibold'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <svg
                class="h-4 w-4 text-purple-500"
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
              Users
            </RouterLink>
          </div>
        </div>

        <!-- Mobile User Account & Sign Out -->
        <div class="border-t border-gray-200 pt-3 pb-1">
          <div class="flex items-center justify-between px-2">
            <div class="flex items-center gap-3">
              <div
                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-semibold text-xs shadow-xs"
              >
                {{ userInitials }}
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-900 leading-tight">
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
              class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
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
          <div class="mt-3">
            <button
              type="button"
              @click="logout"
              class="flex w-full items-center justify-center gap-2 rounded-lg bg-gray-100 py-2 text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors"
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
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
      <RouterView />
    </main>
  </div>
</template>
