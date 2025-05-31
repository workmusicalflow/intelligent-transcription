<template>
  <router-link
    :to="to"
    :class="cardClasses"
    class="block p-6 rounded-lg border transition-all duration-200 hover:shadow-md"
  >
    <div class="flex items-center">
      <div :class="iconClasses" class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center">
        <component :is="iconComponent" class="h-6 w-6" />
      </div>
      <div class="ml-4">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
          {{ title }}
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          {{ description }}
        </p>
      </div>
    </div>
  </router-link>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  DocumentPlusIcon,
  ChatBubbleLeftRightIcon,
  DocumentTextIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'

interface Props {
  title: string
  description: string
  icon: string
  to: string
  color: 'primary' | 'blue' | 'green' | 'purple'
}

const props = defineProps<Props>()

const iconComponents = {
  DocumentPlusIcon,
  ChatBubbleLeftRightIcon,
  DocumentTextIcon,
  ChartBarIcon
}

const iconComponent = computed(() => {
  return iconComponents[props.icon as keyof typeof iconComponents] || DocumentTextIcon
})

const cardClasses = computed(() => [
  'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700',
  'hover:border-gray-300 dark:hover:border-gray-600'
])

const iconClasses = computed(() => {
  const colorClasses = {
    primary: 'bg-primary-100 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400',
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400'
  }
  
  return colorClasses[props.color]
})
</script>

<script lang="ts">
export default {
  name: 'QuickActionCard'
}
</script>