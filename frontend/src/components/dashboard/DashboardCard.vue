<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ title }}
        </h3>
        <div class="flex items-center space-x-2">
          <LoadingSpinner v-if="loading" size="sm" />
          <slot name="actions" />
        </div>
      </div>
    </div>
    <div class="p-6">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import LoadingSpinner from '@components/ui/LoadingSpinner.vue'

interface Props {
  title: string
  loading?: boolean
}

defineProps<Props>()
</script>

<script lang="ts">
export default {
  name: 'DashboardCard'
}
</script>