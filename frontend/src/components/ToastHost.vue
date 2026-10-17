<script setup>
import { useToastStore } from '@/stores/toasts'

const toasts = useToastStore()

const styles = {
  success: 'bg-green-600',
  error: 'bg-red-600',
  info: 'bg-gray-800',
}
</script>

<template>
  <div
    aria-live="polite"
    aria-atomic="false"
    class="pointer-events-none fixed right-4 top-4 z-50 flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2"
  >
    <div
      v-for="t in toasts.items"
      :key="t.id"
      :role="t.type === 'error' ? 'alert' : 'status'"
      class="pointer-events-auto flex items-start justify-between gap-3 rounded-lg px-4 py-3 text-sm text-white shadow-lg"
      :class="styles[t.type] ?? styles.info"
    >
      <span>{{ t.message }}</span>
      <button
        type="button"
        aria-label="Dismiss notification"
        class="text-white/80 hover:text-white"
        @click="toasts.remove(t.id)"
      >
        ✕
      </button>
    </div>
  </div>
</template>
