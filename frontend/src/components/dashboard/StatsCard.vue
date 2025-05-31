<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex items-center">
      <div :class="iconClasses" class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center">
        <component :is="iconComponent" class="h-6 w-6" />
      </div>
      <div class="ml-4 flex-1">
        <div class="flex items-baseline">
          <p class="text-2xl font-semibold text-gray-900 dark:text-white">
            <span v-if="loading" class="skeleton h-8 w-16 inline-block"></span>
            <span v-else>{{ value }}</span>
          </p>
          <p v-if="trend !== undefined" :class="trendClasses" class="ml-2 flex items-baseline text-sm">
            <span>{{ trend > 0 ? '+' : '' }}{{ trend }}%</span>
            <span class="ml-1 text-gray-500 dark:text-gray-400">{{ trendLabel }}</span>
          </p>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ title }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  DocumentTextIcon,
  ClockIcon,
  ChatBubbleLeftRightIcon,
  CurrencyEuroIcon
} from '@heroicons/vue/24/outline'

interface Props {
  title: string
  value: string | number
  trend?: number
  trendLabel?: string
  icon: string
  color: 'blue' | 'green' | 'purple' | 'yellow'
  loading?: boolean
}

const props = defineProps<Props>()

const iconComponents = {
  DocumentTextIcon,
  ClockIcon,
  ChatBubbleLeftRightIcon,
  CurrencyEuroIcon
}

const iconComponent = computed(() => {
  return iconComponents[props.icon as keyof typeof iconComponents] || DocumentTextIcon
})

const iconClasses = computed(() => {
  const colorClasses = {
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400',
    yellow: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400'
  }
  
  return colorClasses[props.color]
})

const trendClasses = computed(() => {
  if (props.trend === undefined) return ''
  
  return props.trend > 0
    ? 'text-green-600 dark:text-green-400'
    : props.trend < 0
    ? 'text-red-600 dark:text-red-400'
    : 'text-gray-600 dark:text-gray-400'
})
</script>

<script lang="ts">
export default {
  name: 'StatsCard'
}
</script>