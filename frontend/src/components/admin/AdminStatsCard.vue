<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <div v-if="loading" class="animate-pulse">
      <div class="flex items-center justify-between mb-4">
        <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
        <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
      </div>
      <div class="space-y-2">
        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
        <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
      </div>
    </div>
    
    <div v-else class="flex items-start justify-between">
      <div class="flex-1">
        <div class="flex items-center mb-2">
          <div 
            :class="[
              'w-12 h-12 rounded-lg flex items-center justify-center mr-3',
              colorClasses.bg
            ]"
          >
            <component 
              :is="iconComponent" 
              :class="['w-6 h-6', colorClasses.text]"
            />
          </div>
          <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">
            {{ title }}
          </h3>
        </div>
        
        <div class="mb-2">
          <p class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ formattedValue }}
          </p>
        </div>
        
        <div v-if="change !== undefined" class="flex items-center">
          <svg
            v-if="change > 0"
            class="w-4 h-4 text-green-500 mr-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
          </svg>
          <svg
            v-else-if="change < 0"
            class="w-4 h-4 text-red-500 mr-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
          </svg>
          <svg
            v-else
            class="w-4 h-4 text-gray-400 mr-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
          </svg>
          
          <span 
            :class="[
              'text-sm font-medium',
              change > 0 ? 'text-green-600 dark:text-green-400' :
              change < 0 ? 'text-red-600 dark:text-red-400' :
              'text-gray-500 dark:text-gray-400'
            ]"
          >
            {{ Math.abs(change) }}%
          </span>
          <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
            vs période précédente
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  title: string
  value: string | number
  change?: number
  icon: string
  color: 'blue' | 'green' | 'yellow' | 'purple' | 'red' | 'indigo'
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

// Mapping des icônes
const iconComponents = {
  UsersIcon: () => import('@heroicons/vue/24/outline').then(m => m.UsersIcon),
  DocumentTextIcon: () => import('@heroicons/vue/24/outline').then(m => m.DocumentTextIcon),
  CurrencyEuroIcon: () => import('@heroicons/vue/24/outline').then(m => m.CurrencyEuroIcon),
  DatabaseIcon: () => import('@heroicons/vue/24/outline').then(m => m.CircleStackIcon),
  ChartBarIcon: () => import('@heroicons/vue/24/outline').then(m => m.ChartBarIcon),
  ClockIcon: () => import('@heroicons/vue/24/outline').then(m => m.ClockIcon),
}

const iconComponent = computed(() => {
  return iconComponents[props.icon as keyof typeof iconComponents] || iconComponents.ChartBarIcon
})

const colorClasses = computed(() => {
  const colors = {
    blue: {
      bg: 'bg-blue-100 dark:bg-blue-900/30',
      text: 'text-blue-600 dark:text-blue-400'
    },
    green: {
      bg: 'bg-green-100 dark:bg-green-900/30',
      text: 'text-green-600 dark:text-green-400'
    },
    yellow: {
      bg: 'bg-yellow-100 dark:bg-yellow-900/30',
      text: 'text-yellow-600 dark:text-yellow-400'
    },
    purple: {
      bg: 'bg-purple-100 dark:bg-purple-900/30',
      text: 'text-purple-600 dark:text-purple-400'
    },
    red: {
      bg: 'bg-red-100 dark:bg-red-900/30',
      text: 'text-red-600 dark:text-red-400'
    },
    indigo: {
      bg: 'bg-indigo-100 dark:bg-indigo-900/30',
      text: 'text-indigo-600 dark:text-indigo-400'
    }
  }
  
  return colors[props.color] || colors.blue
})

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString('fr-FR')
  }
  return props.value
})
</script>

<script lang="ts">
export default {
  name: 'AdminStatsCard'
}
</script>