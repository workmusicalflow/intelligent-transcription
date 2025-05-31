<template>
  <div class="relative">
    <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
    <input
      v-model="searchQuery"
      type="text"
      placeholder="Rechercher..."
      class="pl-9 pr-4 py-2 w-full border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
      @keydown.enter="performSearch"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const searchQuery = ref('')

const performSearch = () => {
  if (searchQuery.value.trim()) {
    // Implement search logic
    console.log('Searching for:', searchQuery.value)
  }
}
</script>

<script lang="ts">
export default {
  name: 'SearchBox'
}
</script>