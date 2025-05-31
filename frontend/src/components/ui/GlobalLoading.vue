<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75">
    <div class="text-center">
      <LoadingSpinner size="lg" color="primary" />
      <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
        Chargement en cours...
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import LoadingSpinner from './LoadingSpinner.vue'
</script>

<script lang="ts">
export default {
  name: 'GlobalLoading'
}
</script>