<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')" />
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="text-center">
          <div class="mx-auto h-12 w-12 bg-gradient-to-br from-primary-500 to-blue-600 rounded-lg flex items-center justify-center mb-4">
            <span class="text-white font-bold text-lg">IT</span>
          </div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            Bienvenue sur Intelligent Transcription !
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            Découvrez toutes les fonctionnalités de notre plateforme de transcription intelligente.
          </p>
          <Button @click="$emit('close')" fullWidth>
            Commencer
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Button from '@components/ui/Button.vue'

defineEmits<{
  close: []
}>()
</script>

<script lang="ts">
export default {
  name: 'WelcomeModal'
}
</script>