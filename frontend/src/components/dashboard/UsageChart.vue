<template>
  <div class="h-64">
    <div v-if="loading" class="flex items-center justify-center h-full">
      <LoadingSpinner size="md" />
    </div>
    <div v-else-if="!data || data.length === 0" class="flex items-center justify-center h-full">
      <p class="text-sm text-gray-500 dark:text-gray-400">Aucune donnée disponible</p>
    </div>
    <div v-else class="h-full flex items-center justify-center">
      <p class="text-sm text-gray-500 dark:text-gray-400">Graphique d'utilisation (placeholder)</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import LoadingSpinner from '@components/ui/LoadingSpinner.vue'

interface UsageData {
  date: string
  transcriptions: number
  hours: number
}

interface Props {
  data: UsageData[]
  loading?: boolean
}

defineProps<Props>()
</script>

<script lang="ts">
export default {
  name: 'UsageChart'
}
</script>