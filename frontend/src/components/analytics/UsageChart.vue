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
    <div v-else class="h-full">
      <!-- Chart container -->
      <div class="relative h-full">
        <!-- Chart area -->
        <div class="absolute inset-0 flex flex-col">
          <!-- Y-axis labels -->
          <div class="flex-1 flex flex-col justify-between text-xs text-gray-500 dark:text-gray-400 pr-4">
            <span>{{ maxValue }}</span>
            <span>{{ Math.round(maxValue * 0.75) }}</span>
            <span>{{ Math.round(maxValue * 0.5) }}</span>
            <span>{{ Math.round(maxValue * 0.25) }}</span>
            <span>0</span>
          </div>
        </div>
        
        <!-- Chart lines -->
        <div class="absolute inset-0 pl-12">
          <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <!-- Grid lines -->
            <defs>
              <pattern id="grid" width="10" height="20" patternUnits="userSpaceOnUse">
                <path d="M 10 0 L 0 0 0 20" fill="none" stroke="currentColor" 
                      class="text-gray-200 dark:text-gray-700" stroke-width="0.5"/>
              </pattern>
            </defs>
            <rect width="100" height="100" fill="url(#grid)" />
            
            <!-- Primary line (transcriptions or selected metric) -->
            <polyline
              v-if="chartPoints.length > 0"
              :points="chartPoints"
              fill="none"
              stroke="#3B82F6"
              stroke-width="2"
              vector-effect="non-scaling-stroke"
            />
            
            <!-- Data points -->
            <circle
              v-for="(point, index) in chartCircles"
              :key="index"
              :cx="point.x"
              :cy="point.y"
              r="3"
              fill="#3B82F6"
              vector-effect="non-scaling-stroke"
              class="cursor-pointer hover:r-4 transition-all"
              @mouseenter="showTooltip($event, point.data, index)"
              @mouseleave="hideTooltip"
            />
            
            <!-- Area fill -->
            <path
              v-if="chartPoints.length > 0"
              :d="areaPath"
              fill="url(#gradient)"
              opacity="0.1"
            />
            
            <!-- Gradient definition -->
            <defs>
              <linearGradient id="gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.3"/>
                <stop offset="100%" stop-color="#3B82F6" stop-opacity="0"/>
              </linearGradient>
            </defs>
          </svg>
        </div>
        
        <!-- X-axis labels -->
        <div class="absolute bottom-0 left-12 right-0 flex justify-between text-xs text-gray-500 dark:text-gray-400 pt-2">
          <span v-for="(label, index) in xAxisLabels" :key="index">
            {{ label }}
          </span>
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
          {{ formatDate(tooltip.data?.date) }}
        </div>
        <div class="text-blue-600 dark:text-blue-400">
          {{ getMetricLabel() }}: {{ formatValue(tooltip.data?.[type] || 0) }}
        </div>
      </div>
    </div>
    
    <!-- Legend -->
    <div class="flex items-center justify-center mt-4 space-x-6">
      <div class="flex items-center">
        <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ getMetricLabel() }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, nextTick } from 'vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

interface UsageDataPoint {
  date: string
  transcriptions: number
  audioHours: number
  cost: number
}

interface Props {
  data: UsageDataPoint[]
  loading?: boolean
  error?: boolean
  type?: 'transcriptions' | 'audioHours' | 'cost'
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: false,
  type: 'transcriptions'
})

// Tooltip state
const tooltip = ref({
  show: false,
  x: 0,
  y: 0,
  data: null as UsageDataPoint | null
})

const tooltipEl = ref<HTMLElement>()

// Chart calculations
const maxValue = computed(() => {
  if (!props.data.length) return 100
  const values = props.data.map(d => d[props.type])
  return Math.max(...values) * 1.1 // Add 10% padding
})

const chartPoints = computed(() => {
  if (!props.data.length) return ''
  
  return props.data.map((point, index) => {
    const x = (index / (props.data.length - 1)) * 100
    const y = 100 - (point[props.type] / maxValue.value) * 100
    return `${x},${y}`
  }).join(' ')
})

const chartCircles = computed(() => {
  if (!props.data.length) return []
  
  return props.data.map((point, index) => ({
    x: (index / (props.data.length - 1)) * 100,
    y: 100 - (point[props.type] / maxValue.value) * 100,
    data: point
  }))
})

const areaPath = computed(() => {
  if (!props.data.length) return ''
  
  const points = props.data.map((point, index) => {
    const x = (index / (props.data.length - 1)) * 100
    const y = 100 - (point[props.type] / maxValue.value) * 100
    return { x, y }
  })
  
  const pathData = points.map((point, index) => 
    `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`
  ).join(' ')
  
  return `${pathData} L 100 100 L 0 100 Z`
})

const xAxisLabels = computed(() => {
  if (!props.data.length) return []
  
  // Show every 5th label to avoid crowding
  return props.data.filter((_, index) => index % 5 === 0 || index === props.data.length - 1)
    .map(d => formatDateShort(d.date))
})

// Tooltip methods
function showTooltip(event: MouseEvent, data: UsageDataPoint, index: number) {
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

// Formatting methods
function formatDate(dateStr: string): string {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

function formatDateShort(dateStr: string): string {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short'
  })
}

function formatValue(value: number): string {
  switch (props.type) {
    case 'transcriptions':
      return value.toString()
    case 'audioHours':
      return `${value.toFixed(1)}h`
    case 'cost':
      return `${value.toFixed(2)}€`
    default:
      return value.toString()
  }
}

function getMetricLabel(): string {
  switch (props.type) {
    case 'transcriptions':
      return 'Transcriptions'
    case 'audioHours':
      return 'Heures audio'
    case 'cost':
      return 'Coût'
    default:
      return 'Valeur'
  }
}

// Icons
const ExclamationIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
    </svg>
  `
}
</script>