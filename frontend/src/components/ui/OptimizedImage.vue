<template>
  <div 
    :class="containerClass"
    :style="{ aspectRatio }"
  >
    <img
      ref="imageRef"
      :alt="alt"
      :class="imageClass"
      :data-src="src"
      :src="placeholderSrc"
      :loading="eager ? 'eager' : 'lazy'"
      :decoding="decoding"
      :fetchpriority="priority"
      @load="handleLoad"
      @error="handleError"
    />
    
    <!-- Loading state -->
    <div
      v-if="isLoading && !isLoaded"
      class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800"
    >
      <div class="animate-pulse">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
    </div>
    
    <!-- Error state -->
    <div
      v-if="isError"
      class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800"
    >
      <div class="text-center text-gray-500">
        <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm">Erreur de chargement</p>
        <button
          v-if="allowRetry"
          @click="retry"
          class="mt-2 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
        >
          Réessayer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useLazyImage } from '@/composables/useIntersectionObserver'

interface Props {
  src: string
  alt: string
  aspectRatio?: string
  eager?: boolean
  priority?: 'high' | 'low' | 'auto'
  decoding?: 'sync' | 'async' | 'auto'
  containerClass?: string
  imageClass?: string
  allowRetry?: boolean
  placeholderColor?: string
}

const props = withDefaults(defineProps<Props>(), {
  aspectRatio: 'auto',
  eager: false,
  priority: 'auto',
  decoding: 'async',
  containerClass: 'relative overflow-hidden',
  imageClass: 'w-full h-full object-cover transition-opacity duration-300',
  allowRetry: true,
  placeholderColor: '#f3f4f6'
})

const imageRef = ref<HTMLImageElement | null>(null)
const retryCount = ref(0)
const maxRetries = 3

// Utiliser le composable de lazy loading
const { isLoaded, isError, isLoading } = useLazyImage(imageRef)

// Générer un placeholder SVG
const placeholderSrc = computed(() => {
  const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300">
      <rect width="400" height="300" fill="${props.placeholderColor}"/>
      <circle cx="200" cy="120" r="40" fill="#e5e7eb"/>
      <path d="M160 180 L200 140 L240 180 L280 140 L320 180 L320 220 L80 220 Z" fill="#e5e7eb"/>
    </svg>
  `
  return `data:image/svg+xml;base64,${btoa(svg)}`
})

// Classes dynamiques pour l'image
const computedImageClass = computed(() => {
  const baseClass = props.imageClass
  const opacityClass = isLoaded.value ? 'opacity-100' : 'opacity-0'
  return `${baseClass} ${opacityClass}`
})

// Gestionnaires d'événements
const handleLoad = () => {
  // L'état est géré par le composable useLazyImage
}

const handleError = () => {
  // L'état est géré par le composable useLazyImage
}

const retry = () => {
  if (retryCount.value < maxRetries && imageRef.value) {
    retryCount.value++
    const img = imageRef.value
    const src = img.dataset.src
    if (src) {
      // Forcer le rechargement en ajoutant un paramètre de cache-busting
      img.dataset.src = `${src}${src.includes('?') ? '&' : '?'}retry=${retryCount.value}`
      // Réinitialiser les états
      isLoaded.value = false
      isError.value = false
      isLoading.value = true
    }
  }
}

onMounted(() => {
  // Si eager loading est activé, charger immédiatement
  if (props.eager && imageRef.value) {
    const img = imageRef.value
    const src = img.dataset.src
    if (src) {
      img.src = src
    }
  }
})
</script>

<style scoped>
/* Amélioration pour les utilisateurs qui préfèrent un mouvement réduit */
@media (prefers-reduced-motion: reduce) {
  .transition-opacity {
    transition: none;
  }
  
  .animate-pulse {
    animation: none;
  }
}

/* Support pour les thèmes à contraste élevé */
@media (prefers-contrast: high) {
  .bg-gray-100 {
    background-color: #ffffff;
    border: 1px solid #000000;
  }
  
  .text-gray-500 {
    color: #000000;
  }
}
</style>