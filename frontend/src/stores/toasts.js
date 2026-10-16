import { defineStore } from 'pinia'
import { ref } from 'vue'

let nextId = 1

export const useToastStore = defineStore('toasts', () => {
  const items = ref([])

  function remove(id) {
    items.value = items.value.filter((t) => t.id !== id)
  }

  function push(message, type = 'success', timeout = 3500) {
    const id = nextId++
    items.value.push({ id, message, type })
    if (timeout) setTimeout(() => remove(id), timeout)
    return id
  }

  const success = (message) => push(message, 'success')
  const error = (message) => push(message, 'error', 6000)
  const info = (message) => push(message, 'info')

  return { items, push, remove, success, error, info }
})
