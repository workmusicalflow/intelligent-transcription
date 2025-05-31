<template>
  <div class="space-y-4">
    <div v-if="activities.length === 0" class="text-center py-4">
      <p class="text-sm text-gray-500 dark:text-gray-400">Aucune activité récente</p>
    </div>
    
    <div v-for="activity in activities" :key="activity.id" class="flex space-x-3">
      <div class="flex-shrink-0">
        <div class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900/20 flex items-center justify-center">
          <div class="h-2 w-2 bg-primary-600 dark:bg-primary-400 rounded-full"></div>
        </div>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-900 dark:text-white">
          {{ activity.title }}
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ activity.description }}
        </p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
          {{ formatTime(activity.timestamp) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatDistanceToNow } from 'date-fns'
import { fr } from 'date-fns/locale'

interface Activity {
  id: string
  type: string
  title: string
  description: string
  timestamp: string
}

interface Props {
  activities: Activity[]
}

defineProps<Props>()

const formatTime = (timestamp: string) => {
  return formatDistanceToNow(new Date(timestamp), { 
    addSuffix: true, 
    locale: fr 
  })
}
</script>

<script lang="ts">
export default {
  name: 'ActivityFeed'
}
</script>