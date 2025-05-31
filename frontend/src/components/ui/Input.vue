<template>
  <div :class="containerClasses">
    <!-- Label -->
    <label
      v-if="label"
      :for="inputId"
      :class="labelClasses"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>

    <!-- Input wrapper -->
    <div class="relative">
      <!-- Icon left -->
      <div
        v-if="iconLeft"
        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
      >
        <component
          :is="iconLeft"
          :class="iconClasses"
        />
      </div>

      <!-- Input element -->
      <component
        :is="inputComponent"
        :id="inputId"
        ref="inputRef"
        :type="currentInputType"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :class="inputClasses"
        :rows="rows"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        v-bind="$attrs"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeydown"
      />

      <!-- Icon right / Clear button / Password toggle -->
      <div
        v-if="iconRight || (clearable && modelValue) || (showPasswordToggle && type === 'password')"
        class="absolute inset-y-0 right-0 pr-3 flex items-center space-x-1"
      >
        <!-- Password toggle -->
        <button
          v-if="showPasswordToggle && type === 'password'"
          type="button"
          class="text-gray-400 hover:text-gray-600 focus:text-gray-600 transition-colors"
          data-testid="password-toggle"
          @click="togglePasswordVisibility"
        >
          <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
          </svg>
          <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
        </button>
        
        <!-- Clear button -->
        <button
          v-if="clearable && modelValue && !disabled"
          type="button"
          class="text-gray-400 hover:text-gray-600 focus:text-gray-600 transition-colors"
          data-testid="clear-button"
          @click="handleClear"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
        
        <!-- Icon right -->
        <component
          v-else-if="iconRight"
          :is="iconRight"
          :class="iconClasses"
        />
      </div>

      <!-- Loading indicator -->
      <div
        v-if="loading"
        class="absolute inset-y-0 right-0 pr-3 flex items-center"
      >
        <LoadingSpinner size="sm" data-testid="loading-spinner" />
      </div>
    </div>

    <!-- Help text -->
    <p
      v-if="helpText"
      :class="helpTextClasses"
    >
      {{ helpText }}
    </p>

    <!-- Error message -->
    <transition name="slide-down">
      <p
        v-if="error"
        class="mt-1 text-sm text-red-600 dark:text-red-400"
        role="alert"
      >
        {{ error }}
      </p>
    </transition>

    <!-- Character count -->
    <div
      v-if="maxlength && showCount"
      class="mt-1 text-xs text-gray-500 text-right"
    >
      {{ characterCount }}/{{ maxlength }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, nextTick } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import LoadingSpinner from './LoadingSpinner.vue'
import { nanoid } from 'nanoid'

interface Props {
  modelValue?: string | number
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'
  inputType?: 'input' | 'textarea'
  label?: string
  placeholder?: string
  helpText?: string
  error?: string
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  loading?: boolean
  clearable?: boolean
  showCount?: boolean
  showPasswordToggle?: boolean
  size?: 'sm' | 'md' | 'lg'
  variant?: 'default' | 'filled' | 'underlined'
  iconLeft?: any
  iconRight?: any
  // Textarea specific
  rows?: number
  // Input specific
  maxlength?: number
  min?: number | string
  max?: number | string
  step?: number | string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  inputType: 'input',
  size: 'md',
  variant: 'default',
  rows: 3,
  clearable: false,
  showCount: false,
  showPasswordToggle: false,
  disabled: false,
  readonly: false,
  required: false,
  loading: false
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
  focus: [event: FocusEvent]
  blur: [event: FocusEvent]
  clear: []
  keydown: [event: KeyboardEvent]
}>()

// Refs
const inputRef = ref<HTMLInputElement | HTMLTextAreaElement>()
const inputId = `input-${nanoid(6)}`
const isFocused = ref(false)
const showPassword = ref(false)

// Computed
const inputComponent = computed(() => props.inputType === 'textarea' ? 'textarea' : 'input')

const currentInputType = computed(() => {
  if (props.type === 'password' && props.showPasswordToggle) {
    return showPassword.value ? 'text' : 'password'
  }
  return props.type
})

const containerClasses = computed(() => [
  'space-y-1'
])

const labelClasses = computed(() => [
  'block text-sm font-medium',
  {
    'text-gray-700 dark:text-gray-300': !props.error,
    'text-red-700 dark:text-red-400': props.error
  }
])

const inputClasses = computed(() => [
  // Base styles
  'block w-full transition-colors duration-200 focus:outline-none',
  
  // Size styles
  {
    'px-3 py-1.5 text-sm': props.size === 'sm',
    'px-3 py-2 text-sm': props.size === 'md',
    'px-4 py-3 text-base': props.size === 'lg'
  },
  
  // Variant styles
  {
    // Default
    'border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-800': 
      props.variant === 'default',
    
    // Filled
    'border-0 bg-gray-100 dark:bg-gray-700 rounded-md': 
      props.variant === 'filled',
    
    // Underlined
    'border-0 border-b-2 border-gray-300 dark:border-gray-600 bg-transparent rounded-none px-0': 
      props.variant === 'underlined'
  },
  
  // State styles
  {
    // Normal state
    'text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400': 
      !props.error && !props.disabled,
    
    // Focus state
    'focus:ring-2 focus:ring-primary-500 focus:border-primary-500': 
      !props.error && props.variant === 'default',
    'focus:bg-gray-50 dark:focus:bg-gray-600': 
      !props.error && props.variant === 'filled',
    'focus:border-primary-500': 
      !props.error && props.variant === 'underlined',
    
    // Error state
    'border-red-300 dark:border-red-600 text-red-900 dark:text-red-100 placeholder-red-300 dark:placeholder-red-500 focus:ring-red-500 focus:border-red-500': 
      props.error,
    
    // Disabled state
    'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed': 
      props.disabled
  },
  
  // Icon padding
  {
    'pl-10': props.iconLeft,
    'pr-10': props.iconRight || props.clearable || props.loading
  }
])

const iconClasses = computed(() => [
  'h-5 w-5',
  {
    'text-gray-400': !props.error,
    'text-red-400': props.error
  }
])

const helpTextClasses = computed(() => [
  'text-sm',
  {
    'text-gray-600 dark:text-gray-400': !props.error,
    'text-red-600 dark:text-red-400': props.error
  }
])

const characterCount = computed(() => {
  return String(props.modelValue || '').length
})

// Methods
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement | HTMLTextAreaElement
  const value = props.type === 'number' ? Number(target.value) : target.value
  emit('update:modelValue', value)
}

const handleFocus = (event: FocusEvent) => {
  isFocused.value = true
  emit('focus', event)
}

const handleBlur = (event: FocusEvent) => {
  isFocused.value = false
  emit('blur', event)
}

const handleClear = () => {
  emit('update:modelValue', '')
  emit('clear')
  nextTick(() => {
    inputRef.value?.focus()
  })
}

const handleKeydown = (event: KeyboardEvent) => {
  emit('keydown', event)
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

// Public methods
const focus = () => {
  inputRef.value?.focus()
}

const blur = () => {
  inputRef.value?.blur()
}

const select = () => {
  inputRef.value?.select()
}

defineExpose({
  focus,
  blur,
  select,
  inputRef
})
</script>

<script lang="ts">
export default {
  name: 'BaseInput',
  inheritAttrs: false
}
</script>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.2s ease;
}
.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}
</style>