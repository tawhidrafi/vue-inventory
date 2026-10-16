<script setup>
import { ref, onMounted, nextTick } from 'vue'

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Scan a barcode or type a SKU, then press Enter',
  },
  autofocus: { type: Boolean, default: false },
})

const emit = defineEmits(['scan'])

const input = ref(null)
const code = ref('')

function submit() {
  const value = code.value.trim()
  if (!value) return

  emit('scan', value)
  code.value = ''
}

function focus() {
  nextTick(() => input.value?.focus())
}

onMounted(() => {
  if (props.autofocus) focus()
})

defineExpose({ focus })
</script>

<template>
  <div class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
      <svg
        class="h-4 w-4"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
      >
        <path d="M4 5v14M8 5v14M12 5v14M17 5v14M20 5v14" />
      </svg>
    </span>
    <input
      ref="input"
      v-model="code"
      type="text"
      :placeholder="placeholder"
      class="w-full rounded border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm"
      @keydown.enter.prevent="submit"
    />
  </div>
</template>
