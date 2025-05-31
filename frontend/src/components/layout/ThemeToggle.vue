<template>
  <button
    @click="toggleTheme"
    class="p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    :title="themeTooltip"
  >
    <SunIcon v-if="uiStore.currentTheme === 'dark'" class="h-6 w-6" />
    <MoonIcon v-else class="h-6 w-6" />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'
import { useUIStore } from '@stores/ui'

const uiStore = useUIStore()

const themeTooltip = computed(() => {
  return uiStore.currentTheme === 'dark' ? 'Mode clair' : 'Mode sombre'
})

const toggleTheme = () => {
  const newTheme = uiStore.currentTheme === 'dark' ? 'light' : 'dark'
  uiStore.setTheme(newTheme)
}
</script>

<script lang="ts">
export default {
  name: 'ThemeToggle'
}
</script>