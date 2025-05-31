<template>
  <aside :class="sidebarClasses">
    <!-- Logo and brand -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
      <router-link to="/dashboard" class="flex items-center space-x-2">
        <div class="h-8 w-8 bg-gradient-to-br from-primary-500 to-blue-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-bold text-sm">IT</span>
        </div>
        <span v-if="!collapsed" class="font-semibold text-gray-900 dark:text-white">
          Intelligent Transcription
        </span>
      </router-link>
      
      <!-- Collapse button (desktop only) -->
      <button
        v-if="!mobile"
        @click="toggleCollapse"
        class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
      >
        <ChevronLeftIcon v-if="!collapsed" class="h-5 w-5" />
        <ChevronRightIcon v-else class="h-5 w-5" />
      </button>
      
      <!-- Close button (mobile only) -->
      <button
        v-if="mobile"
        @click="uiStore.closeSidebar"
        class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
      >
        <XMarkIcon class="h-5 w-5" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
      <!-- Main navigation -->
      <div class="space-y-1">
        <SidebarItem
          v-for="item in mainNavigation"
          :key="item.name"
          :item="item"
          :collapsed="collapsed"
        />
      </div>

      <!-- Separator -->
      <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
        <SidebarItem
          v-for="item in secondaryNavigation"
          :key="item.name"
          :item="item"
          :collapsed="collapsed"
        />
      </div>

      <!-- Admin section -->
      <div
        v-if="authStore.isAdmin"
        class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700"
      >
        <p v-if="!collapsed" class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
          Administration
        </p>
        <SidebarItem
          v-for="item in adminNavigation"
          :key="item.name"
          :item="item"
          :collapsed="collapsed"
        />
      </div>
    </nav>

    <!-- User section -->
    <div class="border-t border-gray-200 dark:border-gray-700 p-4">
      <UserMenu :collapsed="collapsed" />
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  XMarkIcon,
  HomeIcon,
  DocumentTextIcon,
  ChatBubbleLeftRightIcon,
  ChartBarIcon,
  CogIcon,
  UsersIcon,
  ServerIcon
} from '@heroicons/vue/24/outline'

import SidebarItem from './SidebarItem.vue'
import UserMenu from './UserMenu.vue'
import { useAuthStore } from '@stores/auth'
import { useUIStore } from '@stores/ui'

interface Props {
  mobile?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  mobile: false
})

// Stores
const authStore = useAuthStore()
const uiStore = useUIStore()
const route = useRoute()

// State
const collapsed = ref(false)

// Navigation items
const mainNavigation = computed(() => [
  {
    name: 'Tableau de bord',
    href: '/dashboard',
    icon: HomeIcon,
    current: route.name === 'Dashboard'
  },
  {
    name: 'Transcriptions',
    href: '/transcriptions',
    icon: DocumentTextIcon,
    current: route.path.startsWith('/transcriptions'),
    badge: '3' // Example badge
  },
  {
    name: 'Conversations',
    href: '/chat',
    icon: ChatBubbleLeftRightIcon,
    current: route.path.startsWith('/chat')
  },
  {
    name: 'Analytiques',
    href: '/analytics',
    icon: ChartBarIcon,
    current: route.name === 'Analytics'
  }
])

const secondaryNavigation = computed(() => [
  {
    name: 'Paramètres',
    href: '/settings',
    icon: CogIcon,
    current: route.name === 'Settings'
  }
])

const adminNavigation = computed(() => [
  {
    name: 'Utilisateurs',
    href: '/admin/users',
    icon: UsersIcon,
    current: route.path.startsWith('/admin/users')
  },
  {
    name: 'Système',
    href: '/admin/settings',
    icon: ServerIcon,
    current: route.path.startsWith('/admin/settings')
  }
])

// Computed
const sidebarClasses = computed(() => [
  // Base classes
  'flex flex-col h-full bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-300',
  
  // Width based on collapse state and mobile
  {
    'w-64': (!collapsed && !props.mobile) || props.mobile,
    'w-16': collapsed && !props.mobile
  },
  
  // Mobile specific
  props.mobile && 'fixed inset-y-0 left-0 z-30 lg:static lg:z-auto'
])

// Methods
const toggleCollapse = () => {
  collapsed.value = !collapsed.value
}
</script>

<script lang="ts">
export default {
  name: 'Sidebar'
}
</script>