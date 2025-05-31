<template>
  <div class="flex items-center space-x-3">
    <div v-if="!collapsed" class="flex-1 min-w-0">
      <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
        {{ authStore.user?.name }}
      </p>
      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
        {{ authStore.user?.email }}
      </p>
    </div>
    
    <div class="flex-shrink-0">
      <img
        :src="authStore.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(authStore.user?.name || 'User')}&background=3b82f6&color=fff`"
        :alt="authStore.user?.name"
        class="h-8 w-8 rounded-full"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@stores/auth'

interface Props {
  collapsed: boolean
}

defineProps<Props>()

const authStore = useAuthStore()
</script>

<script lang="ts">
export default {
  name: 'UserMenu'
}
</script>