<template>
  <div :class="statusClasses">
    <component
      :is="statusIcon"
      class="h-4 w-4"
    />
    <span class="text-xs font-medium">
      {{ statusText }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  ClockIcon,
  Cog6ToothIcon,
  CheckCircleIcon,
  XCircleIcon,
  StopCircleIcon
} from '@heroicons/vue/24/outline'
import type { TranscriptionStatus as StatusType } from '@/types'

interface Props {
  status: StatusType
  showIcon?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showIcon: true
})

// Status configurations
const statusConfig = {
  pending: {
    text: 'En attente',
    icon: ClockIcon,
    classes: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
  },
  processing: {
    text: 'En cours',
    icon: Cog6ToothIcon,
    classes: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
  },
  completed: {
    text: 'Terminé',
    icon: CheckCircleIcon,
    classes: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
  },
  failed: {
    text: 'Échec',
    icon: XCircleIcon,
    classes: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
  },
  cancelled: {
    text: 'Annulé',
    icon: StopCircleIcon,
    classes: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
  }
}

// Computed
const statusClasses = computed(() => [
  'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
  statusConfig[props.status].classes,
  props.showIcon ? 'space-x-1' : ''
])

const statusIcon = computed(() => statusConfig[props.status].icon)
const statusText = computed(() => statusConfig[props.status].text)
</script>

<script lang="ts">
export default {
  name: 'TranscriptionStatus'
}
</script>