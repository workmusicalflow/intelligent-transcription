<template>
  <div class="flex justify-between items-center">
    <span class="text-sm text-gray-600 dark:text-gray-400">{{ label }}</span>
    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ value }}</span>
  </div>
</template>

<script setup lang="ts">
interface Props {
  label: string
  value: string
}

defineProps<Props>()
</script>

<script lang="ts">
export default {
  name: 'QuickStat'
}
</script>