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
            <span>{{ formatYAxisValue(maxValue) }}</span>
            <span>{{ formatYAxisValue(maxValue * 0.75) }}</span>
            <span>{{ formatYAxisValue(maxValue * 0.5) }}</span>
            <span>{{ formatYAxisValue(maxValue * 0.25) }}</span>
            <span>0</span>
          </div>
        </div>
        
        <!-- Chart lines -->
        <div class="absolute inset-0 pl-12">
          <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <!-- Grid lines -->
            <defs>
              <pattern id="grid-performance" width="10" height="20" patternUnits="userSpaceOnUse">
                <path d="M 10 0 L 0 0 0 20" fill="none" stroke="currentColor" 
                      class="text-gray-200 dark:text-gray-700" stroke-width="0.5"/>
              </pattern>
            </defs>
            <rect width="100" height="100" fill="url(#grid-performance)" />
            
            <!-- Average line -->
            <line
              v-if="averageLineY !== null"
              x1="0" 
              :y1="averageLineY" 
              x2="100" 
              :y2="averageLineY"
              stroke="#F59E0B"
              stroke-width="1.5"
              stroke-dasharray="5,5"
              vector-effect="non-scaling-stroke"
            />
            
            <!-- Performance line -->
            <polyline
              v-if="chartPoints.length > 0"
              :points="chartPoints"
              fill="none"
              :stroke="getLineColor()"
              stroke-width="2.5"
              vector-effect="non-scaling-stroke"
            />
            
            <!-- Data points -->
            <circle
              v-for="(point, index) in chartCircles"
              :key="index"
              :cx="point.x"
              :cy="point.y"
              r="4"
              :fill="getPointColor(point.value)"
              vector-effect="non-scaling-stroke"
              class="cursor-pointer hover:r-5 transition-all"
              @mouseenter="showTooltip($event, point.data, index)"
              @mouseleave="hideTooltip"
            />
            
            <!-- Area fill -->
            <path
              v-if="chartPoints.length > 0"
              :d="areaPath"
              fill="url(#gradient-performance)"
              opacity="0.1"
            />
            
            <!-- Gradient definition -->
            <defs>
              <linearGradient id="gradient-performance" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" :stop-color="getLineColor()" stop-opacity="0.3"/>
                <stop offset="100%" :stop-color="getLineColor()" stop-opacity="0"/>
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
        <div :class="getTooltipValueColor(tooltip.data?.value)">
          {{ getMetricLabel() }}: {{ formatValue(tooltip.data?.value || 0) }}
        </div>
        <div v-if="tooltip.data?.count" class="text-gray-500 dark:text-gray-400 text-xs">
          {{ tooltip.data.count }} transcriptions
        </div>
      </div>
    </div>
    
    <!-- Performance indicators -->
    <div class="flex items-center justify-between mt-4 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
      <div class="flex items-center space-x-4">
        <div class="flex items-center">
          <div class="w-3 h-3 bg-purple-500 rounded-full mr-2"></div>
          <span class="text-sm text-gray-600 dark:text-gray-400">{{ getMetricLabel() }}</span>
        </div>
        <div class="flex items-center">
          <div class="w-3 h-1 bg-yellow-500 mr-2"></div>
          <span class="text-sm text-gray-600 dark:text-gray-400">Moyenne ({{ formatValue(averageValue) }})</span>
        </div>
      </div>
      
      <!-- Performance status -->
      <div :class="[
        'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
        getPerformanceStatusColor()
      ]">
        <component :is="getPerformanceIcon()" class="h-3 w-3 mr-1" />
        {{ getPerformanceStatus() }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

interface PerformanceDataPoint {
  date: string
  value: number
  count?: number
}

interface Props {
  data: PerformanceDataPoint[]
  loading?: boolean
  error?: boolean
  type?: 'processing-time' | 'response-time' | 'queue-time' | 'accuracy'
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: false,
  type: 'processing-time'
})

// Tooltip state
const tooltip = ref({
  show: false,
  x: 0,
  y: 0,
  data: null as PerformanceDataPoint | null
})

// Chart calculations
const maxValue = computed(() => {
  if (!props.data.length) return 100
  const values = props.data.map(d => d.value)
  return Math.max(...values) * 1.1 // Add 10% padding
})

const averageValue = computed(() => {
  if (!props.data.length) return 0
  const sum = props.data.reduce((acc, d) => acc + d.value, 0)
  return sum / props.data.length
})

const averageLineY = computed(() => {
  if (!props.data.length) return null
  return 100 - (averageValue.value / maxValue.value) * 100
})

const chartPoints = computed(() => {
  if (!props.data.length) return ''
  
  return props.data.map((point, index) => {
    const x = (index / (props.data.length - 1)) * 100
    const y = 100 - (point.value / maxValue.value) * 100
    return `${x},${y}`
  }).join(' ')
})

const chartCircles = computed(() => {
  if (!props.data.length) return []
  
  return props.data.map((point, index) => ({
    x: (index / (props.data.length - 1)) * 100,
    y: 100 - (point.value / maxValue.value) * 100,
    data: point,
    value: point.value
  }))
})

const areaPath = computed(() => {
  if (!props.data.length) return ''
  
  const points = props.data.map((point, index) => {
    const x = (index / (props.data.length - 1)) * 100
    const y = 100 - (point.value / maxValue.value) * 100
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

// Color and styling methods
function getLineColor(): string {
  const colorMap = {
    'processing-time': '#8B5CF6', // purple
    'response-time': '#3B82F6', // blue
    'queue-time': '#10B981', // green
    'accuracy': '#F59E0B' // yellow
  }
  return colorMap[props.type] || '#8B5CF6'
}

function getPointColor(value: number): string {
  // Color based on performance relative to average
  const avg = averageValue.value
  if (value <= avg * 0.8) return '#10B981' // green - good performance
  if (value <= avg * 1.2) return '#F59E0B' // yellow - average performance
  return '#EF4444' // red - poor performance
}

function getTooltipValueColor(value?: number): string {
  if (!value) return 'text-gray-600 dark:text-gray-400'
  const avg = averageValue.value
  if (value <= avg * 0.8) return 'text-green-600 dark:text-green-400'
  if (value <= avg * 1.2) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}

function getPerformanceStatus(): string {
  const avg = averageValue.value
  const recent = props.data.slice(-5).reduce((acc, d) => acc + d.value, 0) / 5
  
  if (recent <= avg * 0.8) return 'Excellente'
  if (recent <= avg * 1.2) return 'Correcte'
  return 'À améliorer'
}

function getPerformanceStatusColor(): string {
  const avg = averageValue.value
  const recent = props.data.slice(-5).reduce((acc, d) => acc + d.value, 0) / 5
  
  if (recent <= avg * 0.8) return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
  if (recent <= avg * 1.2) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
  return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
}

function getPerformanceIcon() {
  const avg = averageValue.value
  const recent = props.data.slice(-5).reduce((acc, d) => acc + d.value, 0) / 5
  
  if (recent <= avg * 0.8) return CheckIcon
  if (recent <= avg * 1.2) return ExclamationIcon
  return XIcon
}

// Tooltip methods
function showTooltip(event: MouseEvent, data: PerformanceDataPoint, index: number) {
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
    case 'processing-time':
    case 'response-time':
    case 'queue-time':
      if (value < 60) return `${value.toFixed(1)}s`
      else if (value < 3600) return `${(value / 60).toFixed(1)}min`
      else return `${(value / 3600).toFixed(1)}h`
    case 'accuracy':
      return `${value.toFixed(1)}%`
    default:
      return value.toFixed(1)
  }
}

function formatYAxisValue(value: number): string {
  return formatValue(value)
}

function getMetricLabel(): string {
  switch (props.type) {
    case 'processing-time':
      return 'Temps de traitement'
    case 'response-time':
      return 'Temps de réponse'
    case 'queue-time':
      return 'Temps d\'attente'
    case 'accuracy':
      return 'Précision'
    default:
      return 'Performance'
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

const CheckIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
  `
}

const XIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
  `
}
</script>