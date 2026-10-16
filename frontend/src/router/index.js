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
    path: '/',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('@/views/DashboardView.vue') },
      { path: 'categories', name: 'categories', component: () => import('@/views/CategoriesView.vue') },
      { path: 'products', name: 'products', component: () => import('@/views/ProductsView.vue') },
      { path: 'stock', name: 'stock', component: () => import('@/views/StockView.vue') },
      { path: 'suppliers', name: 'suppliers', component: () => import('@/views/SuppliersView.vue') },
      { path: 'warehouses', name: 'warehouses', component: () => import('@/views/WarehousesView.vue') },
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
  }

  if (to.meta.guest && auth.isLoggedIn) return { name: 'dashboard' }
})

export default router