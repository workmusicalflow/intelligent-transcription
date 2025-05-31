import { ref, reactive, onMounted, onUnmounted, nextTick } from 'vue'
import { debounce, throttle } from 'lodash-es'

interface PerformanceMetrics {
  // Core Web Vitals
  fcp: number | null // First Contentful Paint
  lcp: number | null // Largest Contentful Paint
  fid: number | null // First Input Delay
  cls: number | null // Cumulative Layout Shift
  
  // Custom metrics
  loadTime: number | null
  timeToInteractive: number | null
  memoryUsage: number | null
  
  // Real-time metrics
  frameRate: number
  averageFrameTime: number
  longTasks: number
}

interface ResourceTiming {
  name: string
  duration: number
  size: number
  type: string
}

export function usePerformance() {
  // State
  const metrics = reactive<PerformanceMetrics>({
    fcp: null,
    lcp: null,
    fid: null,
    cls: null,
    loadTime: null,
    timeToInteractive: null,
    memoryUsage: null,
    frameRate: 0,
    averageFrameTime: 0,
    longTasks: 0
  })
  
  const resourceTimings = ref<ResourceTiming[]>([])
  const isMonitoring = ref(false)
  const performanceGrade = ref<'A' | 'B' | 'C' | 'D' | 'F'>('A')
  
  // Frame rate monitoring
  let frameCount = 0
  let lastFrameTime = performance.now()
  let frameRateInterval: ReturnType<typeof setInterval> | null = null
  
  // Long task monitoring
  let longTaskObserver: PerformanceObserver | null = null
  
  // Core Web Vitals measurement
  const measureCoreWebVitals = () => {
    // First Contentful Paint
    const fcpEntry = performance.getEntriesByName('first-contentful-paint')[0] as PerformanceEntry
    if (fcpEntry) {
      metrics.fcp = fcpEntry.startTime
    }
    
    // Largest Contentful Paint
    if ('PerformanceObserver' in window) {
      const lcpObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        const lastEntry = entries[entries.length - 1] as any
        if (lastEntry) {
          metrics.lcp = lastEntry.startTime
        }
      })
      lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] })
    }
    
    // First Input Delay
    if ('PerformanceObserver' in window) {
      const fidObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        entries.forEach((entry: any) => {
          metrics.fid = entry.processingStart - entry.startTime
        })
      })
      fidObserver.observe({ entryTypes: ['first-input'] })
    }
    
    // Cumulative Layout Shift
    if ('PerformanceObserver' in window) {
      let clsValue = 0
      const clsObserver = new PerformanceObserver((list) => {
        list.getEntries().forEach((entry: any) => {
          if (!entry.hadRecentInput) {
            clsValue += entry.value
            metrics.cls = clsValue
          }
        })
      })
      clsObserver.observe({ entryTypes: ['layout-shift'] })
    }
  }
  
  // Load time measurement
  const measureLoadTime = () => {
    window.addEventListener('load', () => {
      const navigationEntry = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
      if (navigationEntry) {
        metrics.loadTime = navigationEntry.loadEventEnd - navigationEntry.fetchStart
      }
    })
  }
  
  // Time to Interactive
  const measureTimeToInteractive = () => {
    // Simplified TTI measurement
    const observer = new PerformanceObserver((list) => {
      const entries = list.getEntries()
      if (entries.length > 0) {
        metrics.timeToInteractive = performance.now()
        observer.disconnect()
      }
    })
    
    if ('PerformanceObserver' in window) {
      observer.observe({ entryTypes: ['measure'] })
    }
    
    // Mark as interactive when Vue is ready
    nextTick(() => {
      if (!metrics.timeToInteractive) {
        metrics.timeToInteractive = performance.now()
      }
    })
  }
  
  // Memory usage monitoring
  const measureMemoryUsage = () => {
    if ('memory' in performance) {
      const memory = (performance as any).memory
      metrics.memoryUsage = memory.usedJSHeapSize / 1024 / 1024 // MB
    }
  }
  
  // Frame rate monitoring
  const startFrameRateMonitoring = () => {
    const updateFrameRate = () => {
      const now = performance.now()
      frameCount++
      
      if (now - lastFrameTime >= 1000) {
        metrics.frameRate = Math.round((frameCount * 1000) / (now - lastFrameTime))
        metrics.averageFrameTime = (now - lastFrameTime) / frameCount
        frameCount = 0
        lastFrameTime = now
      }
      
      if (isMonitoring.value) {
        requestAnimationFrame(updateFrameRate)
      }
    }
    
    requestAnimationFrame(updateFrameRate)
  }
  
  // Long task monitoring
  const startLongTaskMonitoring = () => {
    if ('PerformanceObserver' in window) {
      longTaskObserver = new PerformanceObserver((list) => {
        list.getEntries().forEach(() => {
          metrics.longTasks++
        })
      })
      
      try {
        longTaskObserver.observe({ entryTypes: ['longtask'] })
      } catch (e) {
        console.warn('Long task monitoring not supported')
      }
    }
  }
  
  // Resource timing analysis
  const analyzeResourceTimings = () => {
    const resources = performance.getEntriesByType('resource') as PerformanceResourceTiming[]
    
    resourceTimings.value = resources.map(resource => ({
      name: resource.name,
      duration: resource.responseEnd - resource.startTime,
      size: resource.transferSize || 0,
      type: getResourceType(resource.name)
    }))
  }
  
  const getResourceType = (url: string): string => {
    if (url.includes('.js')) return 'javascript'
    if (url.includes('.css')) return 'stylesheet'
    if (url.match(/\.(jpg|jpeg|png|gif|webp|svg)$/)) return 'image'
    if (url.includes('/api/') || url.includes('/graphql')) return 'api'
    return 'other'
  }
  
  // Performance grade calculation
  const calculatePerformanceGrade = () => {
    let score = 100
    
    // Core Web Vitals scoring
    if (metrics.fcp && metrics.fcp > 1800) score -= 10
    if (metrics.lcp && metrics.lcp > 2500) score -= 15
    if (metrics.fid && metrics.fid > 100) score -= 15
    if (metrics.cls && metrics.cls > 0.1) score -= 10
    
    // Load time scoring
    if (metrics.loadTime && metrics.loadTime > 3000) score -= 10
    
    // Frame rate scoring
    if (metrics.frameRate < 55) score -= 10
    
    // Long tasks scoring
    if (metrics.longTasks > 5) score -= 10
    
    // Memory usage scoring
    if (metrics.memoryUsage && metrics.memoryUsage > 50) score -= 5
    
    if (score >= 90) performanceGrade.value = 'A'
    else if (score >= 80) performanceGrade.value = 'B'
    else if (score >= 70) performanceGrade.value = 'C'
    else if (score >= 60) performanceGrade.value = 'D'
    else performanceGrade.value = 'F'
  }
  
  // Performance optimization utilities
  const optimizeImages = () => {
    // Lazy load images not in viewport
    const images = document.querySelectorAll('img[data-src]')
    
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target as HTMLImageElement
          img.src = img.dataset.src!
          img.removeAttribute('data-src')
          imageObserver.unobserve(img)
        }
      })
    })
    
    images.forEach(img => imageObserver.observe(img))
  }
  
  const preloadCriticalResources = (urls: string[]) => {
    urls.forEach(url => {
      const link = document.createElement('link')
      link.rel = 'preload'
      link.href = url
      
      if (url.includes('.js')) link.as = 'script'
      else if (url.includes('.css')) link.as = 'style'
      else if (url.match(/\.(woff|woff2)$/)) link.as = 'font'
      
      document.head.appendChild(link)
    })
  }
  
  const deferNonCriticalResources = () => {
    // Defer non-critical JavaScript
    const scripts = document.querySelectorAll('script[data-defer]')
    scripts.forEach(script => {
      script.removeAttribute('data-defer')
    })
  }
  
  // Performance monitoring
  const startMonitoring = () => {
    if (isMonitoring.value) return
    
    isMonitoring.value = true
    
    measureCoreWebVitals()
    measureLoadTime()
    measureTimeToInteractive()
    startFrameRateMonitoring()
    startLongTaskMonitoring()
    
    // Update metrics periodically
    const updateInterval = setInterval(() => {
      measureMemoryUsage()
      analyzeResourceTimings()
      calculatePerformanceGrade()
    }, 5000)
    
    // Store interval for cleanup
    frameRateInterval = updateInterval
  }
  
  const stopMonitoring = () => {
    isMonitoring.value = false
    
    if (frameRateInterval) {
      clearInterval(frameRateInterval)
    }
    
    if (longTaskObserver) {
      longTaskObserver.disconnect()
      longTaskObserver = null
    }
  }
  
  // Performance reporting
  const generatePerformanceReport = () => {
    return {
      timestamp: Date.now(),
      url: window.location.href,
      userAgent: navigator.userAgent,
      connectionType: (navigator as any).connection?.effectiveType || 'unknown',
      metrics: { ...metrics },
      resourceTimings: [...resourceTimings.value],
      grade: performanceGrade.value
    }
  }
  
  const sendPerformanceReport = async (endpoint: string) => {
    const report = generatePerformanceReport()
    
    try {
      await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(report)
      })
    } catch (error) {
      console.error('Failed to send performance report:', error)
    }
  }
  
  // Debounced/throttled utilities for performance
  const debouncedAction = debounce((action: Function) => action(), 300)
  const throttledAction = throttle((action: Function) => action(), 100)
  
  // Lifecycle
  onMounted(() => {
    startMonitoring()
    optimizeImages()
  })
  
  onUnmounted(() => {
    stopMonitoring()
  })
  
  return {
    // State
    metrics,
    resourceTimings,
    isMonitoring,
    performanceGrade,
    
    // Methods
    startMonitoring,
    stopMonitoring,
    generatePerformanceReport,
    sendPerformanceReport,
    optimizeImages,
    preloadCriticalResources,
    deferNonCriticalResources,
    
    // Utils
    debouncedAction,
    throttledAction
  }
}

export default usePerformance