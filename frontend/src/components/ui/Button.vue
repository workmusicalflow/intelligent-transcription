<template>
  <component
    :is="tag"
    :type="tag === 'button' ? type : undefined"
    :disabled="disabled || loading"
    :class="buttonClasses"
    v-bind="$attrs"
    @click="handleClick"
  >
    <!-- Loading spinner -->
    <transition name="fade">
      <LoadingSpinner
        v-if="loading"
        :size="spinnerSize"
        class="mr-2"
      />
    </transition>

    <!-- Icon (left) -->
    <component
      v-if="iconLeft && !loading"
      :is="iconLeft"
      :class="iconClasses"
    />

    <!-- Slot content -->
    <span v-if="$slots.default || label" :class="{ 'sr-only': iconOnly }">
      <slot>{{ label }}</slot>
    </span>

    <!-- Icon (right) -->
    <component
      v-if="iconRight && !loading"
      :is="iconRight"
      :class="iconClasses"
    />
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'
import type { ButtonProps } from '@/types'

interface Props extends ButtonProps {
  tag?: 'button' | 'a' | 'router-link'
  label?: string
  iconLeft?: any
  iconRight?: any
  iconOnly?: boolean
  fullWidth?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  tag: 'button',
  variant: 'primary',
  size: 'md',
  type: 'button',
  loading: false,
  disabled: false,
  iconOnly: false,
  fullWidth: false
})

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

// Computed classes
const buttonClasses = computed(() => [
  // Base classes
  'inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
  
  // Size classes
  {
    'px-2 py-1 text-sm rounded-md': props.size === 'sm',
    'px-4 py-2 text-sm rounded-md': props.size === 'md',
    'px-6 py-3 text-base rounded-lg': props.size === 'lg'
  },
  
  // Icon only adjustments
  props.iconOnly && {
    'p-1.5': props.size === 'sm',
    'p-2': props.size === 'md',
    'p-3': props.size === 'lg'
  },
  
  // Variant classes
  {
    // Primary
    'bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white shadow-sm focus:ring-primary-500': 
      props.variant === 'primary',
    
    // Secondary
    'bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-900 border border-gray-300 focus:ring-gray-500 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100 dark:border-gray-600': 
      props.variant === 'secondary',
    
    // Ghost
    'bg-transparent hover:bg-gray-100 active:bg-gray-200 text-gray-700 focus:ring-gray-500 dark:hover:bg-gray-800 dark:text-gray-300 dark:active:bg-gray-700': 
      props.variant === 'ghost',
    
    // Danger
    'bg-red-600 hover:bg-red-700 active:bg-red-800 text-white shadow-sm focus:ring-red-500': 
      props.variant === 'danger'
  },
  
  // Full width
  props.fullWidth && 'w-full',
  
  // Loading state
  props.loading && 'cursor-wait'
])

const iconClasses = computed(() => [
  // Size classes for icons
  {
    'h-4 w-4': props.size === 'sm',
    'h-5 w-5': props.size === 'md',
    'h-6 w-6': props.size === 'lg'
  },
  
  // Spacing
  !props.iconOnly && {
    'mr-2': props.iconLeft,
    'ml-2': props.iconRight
  }
])

const spinnerSize = computed(() => {
  switch (props.size) {
    case 'sm': return 'sm'
    case 'lg': return 'md'
    default: return 'sm'
  }
})

// Event handlers
const handleClick = (event: MouseEvent) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<script lang="ts">
export default {
  name: 'BaseButton',
  inheritAttrs: false
}
</script>