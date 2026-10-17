import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/purchase-orders/:id/print',
      name: 'purchase-order-print',
      component: () => import('@/views/PrintPurchaseOrderView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/sales-orders/:id/print',
      name: 'sales-order-print',
      component: () => import('@/views/PrintSalesOrderView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        { path: '', name: 'dashboard', component: () => import('@/views/DashboardView.vue') },
        {
          path: 'categories',
          name: 'categories',
          component: () => import('@/views/CategoriesView.vue'),
        },
        { path: 'products', name: 'products', component: () => import('@/views/ProductsView.vue') },
        { path: 'stock', name: 'stock', component: () => import('@/views/StockView.vue') },
        { path: 'reports', name: 'reports', component: () => import('@/views/ReportsView.vue') },
        {
          path: 'purchase-orders',
          name: 'purchase-orders',
          component: () => import('@/views/PurchaseOrdersView.vue'),
        },
        {
          path: 'sales-orders',
          name: 'sales-orders',
          component: () => import('@/views/SalesOrdersView.vue'),
        },
        {
          path: 'customers',
          name: 'customers',
          component: () => import('@/views/CustomersView.vue'),
        },
        {
          path: 'suppliers',
          name: 'suppliers',
          component: () => import('@/views/SuppliersView.vue'),
        },
        {
          path: 'warehouses',
          name: 'warehouses',
          component: () => import('@/views/WarehousesView.vue'),
        },
        {
          path: 'activity',
          name: 'activity',
          component: () => import('@/views/ActivityView.vue'),
          meta: { roles: ['admin', 'manager'] },
        },
        {
          path: 'users',
          name: 'users',
          component: () => import('@/views/UsersView.vue'),
          meta: { roles: ['admin'] },
        },
        {
          path: 'settings',
          name: 'settings',
          component: () => import('@/views/SettingsView.vue'),
        },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth) {
    if (!auth.isLoggedIn) return { name: 'login' }

    // after a page refresh the token exists but the user isn't loaded yet
    if (!auth.user) {
      try {
        await auth.fetchUser()
      } catch {
        auth.clear()
        return { name: 'login' }
      }
    }

    // role-gated routes
    if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
      return { name: 'dashboard' }
    }
  }

  if (to.meta.guest && auth.isLoggedIn) return { name: 'dashboard' }
})

export default router
