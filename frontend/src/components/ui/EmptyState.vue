<template>
  <div class="text-center py-12">
    <div class="mx-auto h-12 w-12 text-gray-400">
      <component :is="icon" v-if="icon" class="h-12 w-12" />
      <DocumentTextIcon v-else class="h-12 w-12" />
    </div>
    <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">
      {{ title }}
    </h3>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
      {{ description }}
    </p>
    <div v-if="actionLabel" class="mt-6">
      <Button @click="$emit('action')">
        {{ actionLabel }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { DocumentTextIcon } from '@heroicons/vue/24/outline'
import Button from './Button.vue'

interface Props {
  title: string
  description: string
  actionLabel?: string
  icon?: any
}

defineProps<Props>()

defineEmits<{
  action: []
}>()
</script>

<script lang="ts">
export default {
  name: 'EmptyState'
}
</script>