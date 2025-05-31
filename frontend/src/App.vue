<template>
  <div id="app" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Global Loading -->
    <Transition name="fade">
      <GlobalLoading v-if="appStore.isLoading" />
    </Transition>

    <!-- Main App -->
    <div class="flex h-screen">
      <!-- Sidebar -->
      <Transition name="slide-left">
        <Sidebar v-if="authStore.isAuthenticated && !isMobile" />
      </Transition>

      <!-- Mobile Sidebar Overlay -->
      <Transition name="fade">
        <div
          v-if="uiStore.sidebarOpen && isMobile"
          class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
          @click="uiStore.closeSidebar"
        />
      </Transition>

      <!-- Mobile Sidebar -->
      <Transition name="slide-right">
        <Sidebar
          v-if="authStore.isAuthenticated && isMobile && uiStore.sidebarOpen"
          :mobile="true"
        />
      </Transition>

      <!-- Main Content -->
      <main class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Navigation -->
        <TopNavigation v-if="authStore.isAuthenticated" />

        <!-- Router View -->
        <div class="flex-1 overflow-auto">
          <router-view v-slot="{ Component, route }">
            <Transition :name="route.meta.transition || 'fade'" mode="out-in">
              <Suspense>
                <component :is="Component" :key="route.path" />
                <template #fallback>
                  <div class="flex items-center justify-center h-64">
                    <LoadingSpinner size="lg" />
                  </div>
                </template>
              </Suspense>
            </Transition>
          </router-view>
        </div>
      </main>
    </div>

    <!-- Global Notifications -->
    <NotificationContainer />

    <!-- Global Modals -->
    <ModalContainer />
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useBreakpoints } from '@vueuse/core'

// Components
import Sidebar from '@components/layout/Sidebar.vue'
import TopNavigation from '@components/layout/TopNavigation.vue'
import GlobalLoading from '@components/ui/GlobalLoading.vue'
import LoadingSpinner from '@components/ui/LoadingSpinner.vue'
import NotificationContainer from '@components/ui/NotificationContainer.vue'
import ModalContainer from '@components/ui/ModalContainer.vue'

// Stores
import { useAppStore } from '@stores/app'
import { useAuthStore } from '@stores/auth'
import { useUIStore } from '@stores/ui'

// Composables
const breakpoints = useBreakpoints({
  sm: 640,
  md: 768,
  lg: 1024,
  xl: 1280,
})

// Store instances
const appStore = useAppStore()
const authStore = useAuthStore()
const uiStore = useUIStore()

// Computed
const isMobile = computed(() => !breakpoints.lg.value)

// Lifecycle
onMounted(async () => {
  // Initialize app
  await appStore.initialize()
  
  // Check authentication
  await authStore.checkAuth()
  
  // Set theme
  if (uiStore.theme === 'dark') {
    document.documentElement.classList.add('dark')
  }
})
</script>

<style>
/* Transitions */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-left-enter-active, .slide-left-leave-active {
  transition: transform 0.3s ease;
}
.slide-left-enter-from, .slide-left-leave-to {
  transform: translateX(-100%);
}

.slide-right-enter-active, .slide-right-leave-active {
  transition: transform 0.3s ease;
}
.slide-right-enter-from, .slide-right-leave-to {
  transform: translateX(100%);
}

.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.3s ease;
}
.slide-up-enter-from, .slide-up-leave-to {
  transform: translateY(20px);
  opacity: 0;
}
</style>