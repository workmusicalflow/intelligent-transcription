<template>
  <div :class="containerClasses" v-bind="$attrs">
    <div v-if="variant === 'spinner'" :class="spinnerClasses">
      <svg
        class="animate-spin"
        :class="sizeClasses"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
        />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
    </div>

    <div v-else-if="variant === 'dots'" :class="dotsClasses">
      <div class="animate-bounce" style="animation-delay: 0ms"></div>
      <div class="animate-bounce" style="animation-delay: 150ms"></div>
      <div class="animate-bounce" style="animation-delay: 300ms"></div>
    </div>

    <div v-else-if="variant === 'pulse'" :class="pulseClasses">
      <div class="animate-pulse"></div>
      <div class="animate-pulse" style="animation-delay: 0.5s"></div>
      <div class="animate-pulse" style="animation-delay: 1s"></div>
    </div>

    <!-- Loading text -->
    <span v-if="showText" :class="textClasses">
      {{ text }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { LoadingProps } from '@/types'

interface Props extends LoadingProps {
  text?: string
  showText?: boolean
  color?: 'primary' | 'secondary' | 'white' | 'current'
  center?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  variant: 'spinner',
  color: 'current',
  showText: false,
  center: false,
  text: 'Chargement...'
})

// Computed classes
const containerClasses = computed(() => [
  'flex items-center',
  {
    'justify-center': props.center,
    'flex-col': props.showText && props.center,
    'space-x-2': props.showText && !props.center
  }
])

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'h-4 w-4'
    case 'lg':
      return 'h-8 w-8'
    default:
      return 'h-6 w-6'
  }
})

const spinnerClasses = computed(() => [
  'flex items-center justify-center',
  {
    'text-primary-600': props.color === 'primary',
    'text-gray-600': props.color === 'secondary',
    'text-white': props.color === 'white',
    'text-current': props.color === 'current'
  }
])

const dotsClasses = computed(() => [
  'flex space-x-1',
  {
    'text-primary-600': props.color === 'primary',
    'text-gray-600': props.color === 'secondary',
    'text-white': props.color === 'white',
    'text-current': props.color === 'current'
  }
])

const pulseClasses = computed(() => [
  'flex space-x-1',
  {
    'text-primary-600': props.color === 'primary',
    'text-gray-600': props.color === 'secondary',
    'text-white': props.color === 'white',
    'text-current': props.color === 'current'
  }
])

const textClasses = computed(() => [
  'text-sm font-medium',
  {
    'text-primary-600': props.color === 'primary',
    'text-gray-600': props.color === 'secondary',
    'text-white': props.color === 'white',
    'text-current': props.color === 'current',
    'mt-2': props.center
  }
])

// Dot and pulse sizes
const dotSize = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'h-1 w-1'
    case 'lg':
      return 'h-3 w-3'
    default:
      return 'h-2 w-2'
  }
})
</script>

<script lang="ts">
export default {
  name: 'LoadingSpinner',
  inheritAttrs: false
}
</script>

<style scoped>
.animate-bounce {
  animation: bounce 1.4s infinite ease-in-out both;
}

.animate-pulse {
  animation: pulse 1.4s infinite ease-in-out both;
}

@keyframes bounce {
  0%, 80%, 100% {
    transform: scale(0);
  }
  40% {
    transform: scale(1);
  }
}

@keyframes pulse {
  0%, 80%, 100% {
    transform: scale(0.8);
    opacity: 0.5;
  }
  40% {
    transform: scale(1);
    opacity: 1;
  }
}
</style>