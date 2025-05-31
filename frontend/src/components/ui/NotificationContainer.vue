<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <TransitionGroup name="notification" tag="div">
      <div
        v-for="notification in uiStore.activeNotifications"
        :key="notification.id"
        :class="notificationClasses(notification.type)"
        class="max-w-sm w-full shadow-lg rounded-lg pointer-events-auto overflow-hidden"
      >
        <div class="p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <component :is="getIcon(notification.type)" class="h-6 w-6" />
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
              <p class="text-sm font-medium">
                {{ notification.title }}
              </p>
              <p class="mt-1 text-sm">
                {{ notification.message }}
              </p>
              <div v-if="notification.actions" class="mt-3 flex space-x-2">
                <button
                  v-for="action in notification.actions"
                  :key="action.label"
                  @click="action.action"
                  class="text-sm font-medium underline hover:no-underline"
                >
                  {{ action.label }}
                </button>
              </div>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
              <button
                @click="uiStore.dismissNotification(notification.id)"
                class="rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none"
              >
                <XMarkIcon class="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { 
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { useUIStore } from '@stores/ui'
import type { Notification } from '@/types'

const uiStore = useUIStore()

const getIcon = (type: Notification['type']) => {
  switch (type) {
    case 'success': return CheckCircleIcon
    case 'warning': return ExclamationTriangleIcon
    case 'error': return ExclamationCircleIcon
    default: return InformationCircleIcon
  }
}

const notificationClasses = (type: Notification['type']) => {
  const baseClasses = 'bg-white dark:bg-gray-800 border'
  
  switch (type) {
    case 'success':
      return `${baseClasses} border-green-200 dark:border-green-700 text-green-800 dark:text-green-200`
    case 'warning':
      return `${baseClasses} border-yellow-200 dark:border-yellow-700 text-yellow-800 dark:text-yellow-200`
    case 'error':
      return `${baseClasses} border-red-200 dark:border-red-700 text-red-800 dark:text-red-200`
    default:
      return `${baseClasses} border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200`
  }
}
</script>

<script lang="ts">
export default {
  name: 'NotificationContainer'
}
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>