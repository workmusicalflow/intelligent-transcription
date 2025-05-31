<template>
  <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
    <div class="flex items-center justify-between">
      <!-- Left section -->
      <div class="flex items-center space-x-4">
        <!-- Mobile menu button -->
        <button
          @click="uiStore.toggleSidebar"
          class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          <Bars3Icon class="h-6 w-6" />
        </button>

        <!-- Breadcrumb -->
        <nav class="hidden sm:flex" aria-label="Breadcrumb">
          <ol class="flex items-center space-x-2">
            <li v-for="(crumb, index) in breadcrumbs" :key="crumb.href" class="flex items-center">
              <ChevronRightIcon
                v-if="index > 0"
                class="h-4 w-4 text-gray-400 mr-2"
              />
              <router-link
                :to="crumb.href"
                :class="[
                  'text-sm font-medium transition-colors',
                  index === breadcrumbs.length - 1
                    ? 'text-gray-900 dark:text-white'
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                ]"
              >
                {{ crumb.name }}
              </router-link>
            </li>
          </ol>
        </nav>

        <!-- Page title (mobile) -->
        <h1 class="sm:hidden text-lg font-semibold text-gray-900 dark:text-white">
          {{ currentPageTitle }}
        </h1>
      </div>

      <!-- Right section -->
      <div class="flex items-center space-x-3">
        <!-- Global search -->
        <div class="hidden md:block relative">
          <SearchBox />
        </div>

        <!-- Connection status -->
        <ConnectionStatus />

        <!-- Notifications -->
        <NotificationButton />

        <!-- Theme toggle -->
        <ThemeToggle />

        <!-- User menu -->
        <UserDropdown />
      </div>
    </div>

    <!-- Mobile search -->
    <Transition name="slide-down">
      <div v-if="showMobileSearch" class="mt-3 md:hidden">
        <SearchBox />
      </div>
    </Transition>
  </header>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  Bars3Icon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'

import SearchBox from './SearchBox.vue'
import ConnectionStatus from './ConnectionStatus.vue'
import NotificationButton from './NotificationButton.vue'
import ThemeToggle from './ThemeToggle.vue'
import UserDropdown from './UserDropdown.vue'
import { useUIStore } from '@stores/ui'

// Stores
const uiStore = useUIStore()
const route = useRoute()

// State
const showMobileSearch = ref(false)

// Route to breadcrumb mapping
const routeToBreadcrumbs: Record<string, Array<{ name: string; href: string }>> = {
  Dashboard: [{ name: 'Tableau de bord', href: '/dashboard' }],
  Transcriptions: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Transcriptions', href: '/transcriptions' }
  ],
  CreateTranscription: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Transcriptions', href: '/transcriptions' },
    { name: 'Nouvelle transcription', href: '/transcriptions/create' }
  ],
  TranscriptionDetail: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Transcriptions', href: '/transcriptions' },
    { name: 'Détails', href: route.path }
  ],
  Chat: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Conversations', href: '/chat' }
  ],
  ChatDetail: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Conversations', href: '/chat' },
    { name: 'Conversation', href: route.path }
  ],
  Analytics: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Analytiques', href: '/analytics' }
  ],
  Settings: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Paramètres', href: '/settings' }
  ],
  Profile: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Profil', href: '/profile' }
  ],
  AdminDashboard: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Administration', href: '/admin' }
  ],
  AdminUsers: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Administration', href: '/admin' },
    { name: 'Utilisateurs', href: '/admin/users' }
  ],
  AdminSettings: [
    { name: 'Tableau de bord', href: '/dashboard' },
    { name: 'Administration', href: '/admin' },
    { name: 'Paramètres', href: '/admin/settings' }
  ]
}

// Computed
const breadcrumbs = computed(() => {
  const routeName = route.name as string
  return routeToBreadcrumbs[routeName] || [{ name: 'Tableau de bord', href: '/dashboard' }]
})

const currentPageTitle = computed(() => {
  const currentBreadcrumb = breadcrumbs.value[breadcrumbs.value.length - 1]
  return currentBreadcrumb?.name || 'Page'
})

// Methods
const toggleMobileSearch = () => {
  showMobileSearch.value = !showMobileSearch.value
}
</script>

<script lang="ts">
export default {
  name: 'TopNavigation'
}
</script>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>