<template>
  <div class="relative">
    <button
      @click="toggleNotifications"
      class="p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    >
      <BellIcon class="h-6 w-6" />
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { BellIcon } from '@heroicons/vue/24/outline'

// Mock data - replace with real notification system
const unreadCount = computed(() => 0)

const toggleNotifications = () => {
  // Implement notification panel toggle
  console.log('Toggle notifications')
}
</script>

<script lang="ts">
export default {
  name: 'NotificationButton'
}
</script>