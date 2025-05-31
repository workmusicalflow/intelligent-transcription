<template>
  <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl transform transition-all max-w-md w-full">
    <div class="p-6">
      <div class="flex items-center">
        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20">
          <ExclamationTriangleIcon class="h-6 w-6 text-red-600 dark:text-red-400" />
        </div>
        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            {{ title }}
          </h3>
          <div class="mt-2">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ message }}
            </p>
          </div>
        </div>
      </div>
    </div>
    
    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
      <Button
        variant="danger"
        class="w-full sm:ml-3 sm:w-auto"
        @click="handleConfirm"
      >
        {{ confirmText }}
      </Button>
      <Button
        variant="secondary"
        class="mt-3 w-full sm:mt-0 sm:w-auto"
        @click="handleCancel"
      >
        {{ cancelText }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import Button from './Button.vue'

interface Props {
  title: string
  message: string
  confirmText?: string
  cancelText?: string
  onConfirm?: () => void
  onCancel?: () => void
}

const props = withDefaults(defineProps<Props>(), {
  confirmText: 'Confirmer',
  cancelText: 'Annuler'
})

const emit = defineEmits<{
  close: []
}>()

const handleConfirm = () => {
  props.onConfirm?.()
  emit('close')
}

const handleCancel = () => {
  props.onCancel?.()
  emit('close')
}
</script>

<script lang="ts">
export default {
  name: 'ConfirmationModal'
}
</script>