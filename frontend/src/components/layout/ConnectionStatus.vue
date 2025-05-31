<template>
  <div class="flex items-center">
    <div :class="statusClasses" class="h-2 w-2 rounded-full"></div>
    <span v-if="showText" class="ml-2 text-xs text-gray-500 dark:text-gray-400">
      {{ statusText }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@stores/app'

interface Props {
  showText?: boolean
}

withDefaults(defineProps<Props>(), {
  showText: false
})

const appStore = useAppStore()

const statusClasses = computed(() => {
  switch (appStore.connectionStatus) {
    case 'online':
      return 'bg-green-400'
    case 'offline':
      return 'bg-red-400'
    case 'reconnecting':
      return 'bg-yellow-400 animate-pulse'
    default:
      return 'bg-gray-400'
  }
})

const statusText = computed(() => {
  switch (appStore.connectionStatus) {
    case 'online':
      return 'En ligne'
    case 'offline':
      return 'Hors ligne'
    case 'reconnecting':
      return 'Reconnexion...'
    default:
      return 'Inconnu'
  }
})
</script>

<script lang="ts">
export default {
  name: 'ConnectionStatus'
}
</script>