<template>
  <div class="h-80">
    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center h-full">
      <LoadingSpinner size="lg" />
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="flex items-center justify-center h-full text-red-500 dark:text-red-400">
      <div class="text-center">
        <ExclamationIcon class="h-12 w-12 mx-auto mb-2" />
        <p>Erreur lors du chargement des données</p>
      </div>
    </div>
    
    <!-- Chart -->
    <div v-else class="h-full flex items-center justify-center">
      <div class="flex items-center space-x-8">
        <!-- Pie Chart -->
        <div class="relative">
          <svg width="200" height="200" viewBox="0 0 200 200" class="transform -rotate-90">
            <!-- Background circle -->
            <circle
              cx="100"
              cy="100"
              r="80"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              class="text-gray-200 dark:text-gray-700"
            />
            
            <!-- Pie segments -->
            <path
              v-for="(segment, index) in pieSegments"
              :key="index"
              :d="segment.path"
              :fill="segment.color"
              :stroke="segment.color"
              stroke-width="2"
              class="cursor-pointer hover:opacity-80 transition-opacity"
              @mouseenter="showTooltip($event, segment.data)"
              @mouseleave="hideTooltip"
            />
          </svg>
          
          <!-- Center text -->
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center">
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ totalCount }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                Total
              </div>
            </div>
          </div>
        </div>
        
        <!-- Legend -->
        <div class="space-y-3">
          <div
            v-for="(item, index) in data"
            :key="index"
            class="flex items-center space-x-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 rounded-lg transition-colors"
            @mouseenter="highlightSegment(index)"
            @mouseleave="unhighlightSegment"
          >
            <div 
              class="w-4 h-4 rounded-full flex-shrink-0"
              :style="{ backgroundColor: item.color }"
            ></div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ item.language }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ item.count }} transcriptions
              </div>
            </div>
            <div class="text-sm font-medium text-gray-900 dark:text-white">
              {{ item.percentage.toFixed(1) }}%
            </div>
          </div>
        </div>
      </div>
      
      <!-- Tooltip -->
      <div
        v-if="tooltip.show"
        ref="tooltipEl"
        class="absolute z-10 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-3 text-sm pointer-events-none"
        :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
      >
        <div class="font-medium text-gray-900 dark:text-white">
          {{ tooltip.data?.language }}
        </div>
        <div class="text-gray-600 dark:text-gray-400">
          {{ tooltip.data?.count }} transcriptions ({{ tooltip.data?.percentage.toFixed(1) }}%)
        </div>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-if="!loading && !error && data.length === 0" class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
      <div class="text-center">
        <ChartPieIcon class="h-12 w-12 mx-auto mb-2" />
        <p>Aucune donnée disponible</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

interface LanguageDataPoint {
  language: string
  count: number
  percentage: number
  color: string
}

interface Props {
  data: LanguageDataPoint[]
  loading?: boolean
  error?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: false
})

// Tooltip state
const tooltip = ref({
  show: false,
  x: 0,
  y: 0,
  data: null as LanguageDataPoint | null
})

const highlightedIndex = ref<number | null>(null)

// Chart calculations
const totalCount = computed(() => {
  return props.data.reduce((sum, item) => sum + item.count, 0)
})

const pieSegments = computed(() => {
  if (!props.data.length) return []
  
  let currentAngle = 0
  const radius = 80
  const centerX = 100
  const centerY = 100
  
  return props.data.map((item, index) => {
    const percentage = item.percentage / 100
    const angle = percentage * 2 * Math.PI
    
    const startAngle = currentAngle
    const endAngle = currentAngle + angle
    
    const x1 = centerX + radius * Math.cos(startAngle)
    const y1 = centerY + radius * Math.sin(startAngle)
    const x2 = centerX + radius * Math.cos(endAngle)
    const y2 = centerY + radius * Math.sin(endAngle)
    
    const largeArc = angle > Math.PI ? 1 : 0
    
    const path = [
      `M ${centerX} ${centerY}`,
      `L ${x1} ${y1}`,
      `A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2}`,
      'Z'
    ].join(' ')
    
    currentAngle += angle
    
    return {
      path,
      color: item.color,
      data: item,
      highlighted: highlightedIndex.value === index
    }
  })
})

// Methods
function showTooltip(event: MouseEvent, data: LanguageDataPoint) {
  const rect = (event.target as HTMLElement).getBoundingClientRect()
  tooltip.value = {
    show: true,
    x: rect.left + 10,
    y: rect.top - 10,
    data
  }
}

function hideTooltip() {
  tooltip.value.show = false
}

function highlightSegment(index: number) {
  highlightedIndex.value = index
}

function unhighlightSegment() {
  highlightedIndex.value = null
}

// Icons
const ExclamationIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
    </svg>
  `
}

const ChartPieIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
    </svg>
  `
}
</script>