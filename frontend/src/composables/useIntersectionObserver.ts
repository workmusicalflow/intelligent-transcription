import { ref, onMounted, onUnmounted, type Ref } from 'vue'

interface UseIntersectionObserverOptions {
  threshold?: number | number[]
  root?: Element | null
  rootMargin?: string
  once?: boolean
}

/**
 * Composable pour l'Intersection Observer API
 * Utile pour le lazy loading d'images et l'animation des éléments au scroll
 */
export function useIntersectionObserver(
  target: Ref<Element | null>,
  callback: (entries: IntersectionObserverEntry[]) => void,
  options: UseIntersectionObserverOptions = {}
) {
  const {
    threshold = 0.1,
    root = null,
    rootMargin = '0px',
    once = false
  } = options

  const isIntersecting = ref(false)
  const isSupported = ref(true)
  let observer: IntersectionObserver | null = null

  const cleanup = () => {
    if (observer) {
      observer.disconnect()
      observer = null
    }
  }

  const observe = () => {
    if (!window.IntersectionObserver) {
      isSupported.value = false
      callback([])
      return
    }

    if (!target.value) return

    cleanup()

    observer = new IntersectionObserver((entries) => {
      isIntersecting.value = entries[0]?.isIntersecting ?? false
      callback(entries)
      
      if (once && isIntersecting.value) {
        cleanup()
      }
    }, {
      threshold,
      root,
      rootMargin
    })

    observer.observe(target.value)
  }

  onMounted(() => {
    observe()
  })

  onUnmounted(() => {
    cleanup()
  })

  return {
    isIntersecting,
    isSupported,
    observer,
    cleanup
  }
}

/**
 * Composable pour le lazy loading d'images
 */
export function useLazyImage(imageRef: Ref<HTMLImageElement | null>) {
  const isLoaded = ref(false)
  const isError = ref(false)
  const isLoading = ref(false)

  const { isIntersecting } = useIntersectionObserver(
    imageRef,
    (entries) => {
      if (entries[0]?.isIntersecting && imageRef.value) {
        loadImage()
      }
    },
    { once: true, threshold: 0.1, rootMargin: '50px' }
  )

  const loadImage = () => {
    if (!imageRef.value || isLoaded.value || isLoading.value) return

    isLoading.value = true
    const img = imageRef.value
    const src = img.dataset.src

    if (!src) {
      isError.value = true
      isLoading.value = false
      return
    }

    const tempImg = new Image()
    tempImg.onload = () => {
      img.src = src
      img.removeAttribute('data-src')
      isLoaded.value = true
      isLoading.value = false
    }
    tempImg.onerror = () => {
      isError.value = true
      isLoading.value = false
    }
    tempImg.src = src
  }

  return {
    isLoaded,
    isError,
    isLoading,
    isIntersecting
  }
}

/**
 * Composable pour l'animation au scroll
 */
export function useScrollAnimation(
  elementRef: Ref<Element | null>,
  animationClass: string = 'animate-fade-in-up'
) {
  const hasAnimated = ref(false)

  useIntersectionObserver(
    elementRef,
    (entries) => {
      if (entries[0]?.isIntersecting && !hasAnimated.value) {
        elementRef.value?.classList.add(animationClass)
        hasAnimated.value = true
      }
    },
    { once: true, threshold: 0.2 }
  )

  return {
    hasAnimated
  }
}