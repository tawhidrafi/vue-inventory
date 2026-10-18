import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/stocks', () => ({
  stocksApi: {
    dashboard: vi.fn(),
  },
}))

vi.mock('@/api/products', () => ({
  productsApi: {
    lookup: vi.fn(),
  },
}))

import { stocksApi } from '@/api/stocks'
import DashboardView from '@/views/DashboardView.vue'
import { useAuthStore } from '@/stores/auth'

const mockDashboardData = {
  metrics: {
    products_count: 12,
    categories_count: 5,
    suppliers_count: 3,
    customers_count: 4,
    warehouses_count: 3,
    total_units: 340,
    cost_value: 12500.5,
    retail_value: 18900.0,
    potential_margin: 6399.5,
    margin_percentage: 33.9,
    low_stock_count: 2,
    out_of_stock_count: 1,
    sales_orders: {
      total: 8,
      pending: 3,
      shipped: 5,
      revenue: 4500.0,
    },
    purchase_orders: {
      total: 5,
      pending: 2,
      received: 3,
      spend: 6200.0,
    },
    movements_last_30_days: {
      in_units: 120,
      out_units: 45,
      total_count: 28,
    },
  },
  low_stock_items: [
    {
      id: 4,
      name: 'USB-C Triple Display Docking Station',
      sku: 'PRD-00004',
      barcode: '1234567890123',
      unit: 'pcs',
      category_name: 'Electronics',
      stock_total: 2,
      reorder_level: 10,
      deficit: 8,
      cost_price: 45.0,
      sell_price: 79.99,
    },
  ],
  warehouses: [
    {
      warehouse_id: 1,
      warehouse_name: 'Main Distribution Center',
      units: 240,
      share_percentage: 70.6,
      cost_value: 8800.0,
      retail_value: 13200.0,
      products_count: 10,
    },
  ],
  categories: [
    {
      category_id: 1,
      category_name: 'Electronics',
      units: 150,
      share_percentage: 44.1,
      cost_value: 6500.0,
      retail_value: 9800.0,
      products_count: 6,
    },
  ],
  recent_sales_orders: [
    {
      id: 1,
      number: 'SO-00001',
      customer_name: 'Jane Cooper',
      order_date: '2026-10-18',
      status: 'shipped',
      total: 755.84,
    },
  ],
  recent_purchase_orders: [
    {
      id: 1,
      number: 'PO-00001',
      supplier_name: 'TechCorp Global Logistics',
      order_date: '2026-10-18',
      status: 'received',
      total: 3550.0,
    },
  ],
  recent_movements: [
    {
      id: 1,
      product_name: 'USB-C Cable',
      product_sku: 'PRD-00001',
      warehouse_name: 'Main Distribution Center',
      user_name: 'Admin',
      type: 'in',
      quantity: 50,
      balance_after: 100,
      created_at: new Date().toISOString(),
    },
  ],
  recent_activities: [
    {
      id: 1,
      user_name: 'Admin',
      action: 'created',
      subject_type: 'Product',
      description: 'Created product "Wireless Mouse"',
      created_at: new Date().toISOString(),
    },
  ],
}

describe('DashboardView', () => {
  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    vi.clearAllMocks()

    const auth = useAuthStore()
    auth.applyUser({
      name: 'Sarah Connor',
      role: 'admin',
    })
  })

  it('renders executive KPI metrics and valuation', async () => {
    stocksApi.dashboard.mockResolvedValue({ data: mockDashboardData })

    const wrapper = mount(DashboardView, {
      global: {
        plugins: [pinia],
        stubs: {
          RouterLink: {
            template: '<a><slot /></a>',
          },
        },
      },
    })

    await flushPromises()

    expect(wrapper.text()).toContain('Operations Dashboard')
    expect(wrapper.text()).toContain('Sarah Connor')
    expect(wrapper.text()).toContain('$12,500.50')
    expect(wrapper.text()).toContain('340')
    expect(wrapper.text()).toContain('$4,500.00')
    expect(wrapper.text()).toContain('2')
    expect(wrapper.text()).toContain('items low')
  })

  it('renders critical low stock alerts and warehouse breakdown', async () => {
    stocksApi.dashboard.mockResolvedValue({ data: mockDashboardData })

    const wrapper = mount(DashboardView, {
      global: {
        plugins: [pinia],
        stubs: {
          RouterLink: {
            template: '<a><slot /></a>',
          },
        },
      },
    })

    await flushPromises()

    expect(wrapper.text()).toContain('Action Required: Low Stock & Reorder Alerts')
    expect(wrapper.text()).toContain('USB-C Triple Display Docking Station')
    expect(wrapper.text()).toContain('PRD-00004')
    expect(wrapper.text()).toContain('Main Distribution Center')
    expect(wrapper.text()).toContain('Electronics')
  })

  it('allows switching between operations tabs (Sales, Purchases, Movements)', async () => {
    stocksApi.dashboard.mockResolvedValue({ data: mockDashboardData })

    const wrapper = mount(DashboardView, {
      global: {
        plugins: [pinia],
        stubs: {
          RouterLink: {
            template: '<a><slot /></a>',
          },
        },
      },
    })

    await flushPromises()

    // Default tab is Sales
    expect(wrapper.text()).toContain('SO-00001')
    expect(wrapper.text()).toContain('Jane Cooper')

    // Click Purchases tab
    const purchaseTabBtn = wrapper
      .findAll('button')
      .find((b) => b.text().includes('Recent Purchases'))
    expect(purchaseTabBtn).toBeDefined()
    await purchaseTabBtn.trigger('click')

    expect(wrapper.text()).toContain('PO-00001')
    expect(wrapper.text()).toContain('TechCorp Global Logistics')

    // Click Movements tab
    const movementsTabBtn = wrapper
      .findAll('button')
      .find((b) => b.text().includes('Stock Movements'))
    expect(movementsTabBtn).toBeDefined()
    await movementsTabBtn.trigger('click')

    expect(wrapper.text()).toContain('USB-C Cable')
    expect(wrapper.text()).toContain('+50')
  })

  it('displays error banner when loading fails', async () => {
    stocksApi.dashboard.mockRejectedValue(new Error('Network error'))

    const wrapper = mount(DashboardView, {
      global: {
        plugins: [pinia],
        stubs: {
          RouterLink: {
            template: '<a><slot /></a>',
          },
        },
      },
    })

    await flushPromises()

    expect(wrapper.text()).toContain('Failed to load dashboard statistics')
  })
})
