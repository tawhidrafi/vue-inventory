<script setup>
import { ref, onMounted } from 'vue'
import { profileApi } from '@/api/profile'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toasts'

const auth = useAuthStore()
const toast = useToastStore()

const preferences = ref({
  notify_low_stock: false,
  notify_purchase_orders: false,
})
const loading = ref(true)
const saving = ref(false)

function syncFromUser() {
  preferences.value = {
    notify_low_stock: !!auth.user?.notify_low_stock,
    notify_purchase_orders: !!auth.user?.notify_purchase_orders,
  }
}

onMounted(async () => {
  try {
    if (!auth.user) await auth.fetchUser()
    syncFromUser()
  } catch {
    toast.error('Could not load your profile.')
  } finally {
    loading.value = false
  }
})

async function save() {
  saving.value = true
  try {
    const { data } = await profileApi.updatePreferences(preferences.value)
    auth.applyUser(data)
    syncFromUser()
    toast.success('Preferences saved.')
  } catch {
    toast.error('Could not save your preferences.')
    syncFromUser()
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl">
    <h2 class="mb-4 text-xl font-semibold">Settings</h2>

    <div class="mb-6 rounded-lg bg-white p-5 shadow">
      <h3 class="mb-3 text-sm font-semibold text-gray-500 uppercase">Account</h3>
      <p class="text-sm"><span class="text-gray-500">Name:</span> {{ auth.user?.name ?? '-' }}</p>
      <p class="text-sm"><span class="text-gray-500">Email:</span> {{ auth.user?.email ?? '-' }}</p>
      <p class="text-sm"><span class="text-gray-500">Role:</span> {{ auth.user?.role ?? '-' }}</p>
    </div>

    <div class="rounded-lg bg-white p-5 shadow">
      <h3 class="mb-1 text-sm font-semibold text-gray-500 uppercase">Email notifications</h3>
      <p class="mb-4 text-sm text-gray-500">
        Choose which alerts you want delivered to your email address.
      </p>

      <label class="mb-3 flex items-start gap-3">
        <input
          v-model="preferences.notify_low_stock"
          type="checkbox"
          class="mt-0.5 h-4 w-4 rounded border-gray-300"
        />
        <span class="text-sm">
          <span class="font-medium">Low stock alerts</span>
          <span class="block text-gray-500">
            Emailed when a product falls to or below its reorder level.
          </span>
        </span>
      </label>

      <label class="flex items-start gap-3">
        <input
          v-model="preferences.notify_purchase_orders"
          type="checkbox"
          class="mt-0.5 h-4 w-4 rounded border-gray-300"
        />
        <span class="text-sm">
          <span class="font-medium">Purchase order received</span>
          <span class="block text-gray-500">
            Emailed when a purchase order has been fully received.
          </span>
        </span>
      </label>

      <div class="mt-5">
        <button
          :disabled="loading || saving"
          class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"
          @click="save"
        >
          {{ saving ? 'Saving...' : 'Save preferences' }}
        </button>
      </div>
    </div>
  </div>
</template>
