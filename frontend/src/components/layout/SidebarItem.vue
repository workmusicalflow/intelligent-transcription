<template>
  <router-link
    :to="item.href"
    :class="itemClasses"
    :title="collapsed ? item.name : undefined"
  >
    <!-- Icon -->
    <component
      :is="item.icon"
      :class="iconClasses"
    />
    
    <!-- Label -->
    <span v-if="!collapsed" class="text-sm font-medium truncate">
      {{ item.name }}
    </span>
    
    <!-- Badge -->
    <span
      v-if="item.badge && !collapsed"
      :class="badgeClasses"
    >
      {{ item.badge }}
    </span>
    
    <!-- Tooltip for collapsed state -->
    <Transition name="fade">
      <div
        v-if="collapsed && showTooltip"
        class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap z-50"
        style="top: 50%; transform: translateY(-50%)"
      >
        {{ item.name }}
        <div class="absolute right-full top-1/2 transform -translate-y-1/2 border-r-4 border-r-gray-900 border-y-4 border-y-transparent"></div>
      </div>
    </Transition>
  </router-link>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface NavigationItem {
  name: string
  href: string
  icon: any
  current: boolean
  badge?: string
}

interface Props {
  item: NavigationItem
  collapsed: boolean
}

const props = defineProps<Props>()

// State
const showTooltip = ref(false)

// Computed
const itemClasses = computed(() => [
  // Base classes
  'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-all duration-200 relative',
  
  // Active state
  props.item.current
    ? 'bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400'
    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
  
  // Collapsed state
  {
    'justify-center': props.collapsed,
    'justify-between': !props.collapsed && props.item.badge,
    'space-x-3': !props.collapsed
  }
])

const iconClasses = computed(() => [
  'flex-shrink-0 h-5 w-5',
  props.item.current
    ? 'text-primary-600 dark:text-primary-400'
    : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300'
])

const badgeClasses = computed(() => [
  'inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full',
  props.item.current
    ? 'bg-primary-200 dark:bg-primary-800 text-primary-800 dark:text-primary-200'
    : 'bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200'
])

// Event handlers
const handleMouseEnter = () => {
  if (props.collapsed) {
    showTooltip.value = true
  }
}

const handleMouseLeave = () => {
  showTooltip.value = false
}
</script>

<script lang="ts">
export default {
  name: 'SidebarItem'
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>