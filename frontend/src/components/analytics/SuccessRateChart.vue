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
            <span>100%</span>
            <span>75%</span>
            <span>50%</span>
            <span>25%</span>
            <span>0%</span>
          </div>
        </div>
        
        <!-- Chart lines -->
        <div class="absolute inset-0 pl-12">
          <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <!-- Grid lines -->
            <defs>
              <pattern id="grid-success" width="10" height="20" patternUnits="userSpaceOnUse">
                <path d="M 10 0 L 0 0 0 20" fill="none" stroke="currentColor" 
                      class="text-gray-200 dark:text-gray-700" stroke-width="0.5"/>
              </pattern>
            </defs>
            <rect width="100" height="100" fill="url(#grid-success)" />
            
            <!-- Target line (95% success rate) -->
            <line
              x1="0" 
              y1="5" 
              x2="100" 
              y2="5"
              stroke="#10B981"
              stroke-width="1.5"
              stroke-dasharray="5,5"
              vector-effect="non-scaling-stroke"
            />
            
            <!-- Success rate line -->
            <polyline
              v-if="chartPoints.length > 0"
              :points="chartPoints"
              fill="none"
              stroke="#3B82F6"
              stroke-width="3"
              vector-effect="non-scaling-stroke"
            />
            
            <!-- Data points -->
            <circle
              v-for="(point, index) in chartCircles"
              :key="index"
              :cx="point.x"
              :cy="point.y"
              r="4"
              :fill="getPointColor(point.successRate)"
              stroke="white"
              stroke-width="2"
              vector-effect="non-scaling-stroke"
              class="cursor-pointer hover:r-5 transition-all"
              @mouseenter="showTooltip($event, point.data, index)"
              @mouseleave="hideTooltip"
            />
            
            <!-- Area fill -->
            <path
              v-if="chartPoints.length > 0"
              :d="areaPath"
              fill="url(#gradient-success)"
              opacity="0.15"
            />
            
            <!-- Gradient definition -->
            <defs>
              <linearGradient id="gradient-success" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.4"/>
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
        <div :class="getSuccessRateColor(tooltip.data?.successRate)">
          Taux de succès: {{ tooltip.data?.successRate.toFixed(1) }}%
        </div>
        <div class="text-gray-500 dark:text-gray-400 text-xs">
          {{ tooltip.data?.successful }}/{{ tooltip.data?.total }} transcriptions
        </div>
        <div v-if="tooltip.data?.errors && tooltip.data?.errors > 0" class="text-red-500 text-xs">
          {{ tooltip.data?.errors }} erreurs
        </div>
      </div>
    </div>
    
    <!-- Success rate summary -->
    <div class="grid grid-cols-3 gap-4 mt-4 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
      <div class="text-center">
        <div class="text-lg font-bold text-gray-900 dark:text-white">
          {{ overallSuccessRate.toFixed(1) }}%
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">Taux global</div>
      </div>
      <div class="text-center">
        <div :class="[
          'text-lg font-bold',
          getSuccessRateColor(recentSuccessRate)
        ]">
          {{ recentSuccessRate.toFixed(1) }}%
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">7 derniers jours</div>
      </div>
      <div class="text-center">
        <div :class="[
          'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
          getSuccessRateStatusColor()
        ]">
          <component :is="getSuccessRateIcon()" class="h-3 w-3 mr-1" />
          {{ getSuccessRateStatus() }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Statut</div>
      </div>
    </div>
    
    <!-- Legend -->
    <div class="flex items-center justify-center mt-4 space-x-6">
      <div class="flex items-center">
        <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
        <span class="text-sm text-gray-600 dark:text-gray-400">Taux de succès</span>
      </div>
      <div class="flex items-center">
        <div class="w-3 h-1 bg-green-500 mr-2"></div>
        <span class="text-sm text-gray-600 dark:text-gray-400">Objectif (95%)</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

interface SuccessRateDataPoint {
  date: string
  successful: number
  total: number
  errors: number
  successRate: number
}

interface Props {
  data: SuccessRateDataPoint[]
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
  data: null as SuccessRateDataPoint | null
})

// Chart calculations
const chartPoints = computed(() => {
  if (!props.data.length) return ''
  
  return props.data.map((point, index) => {
    const x = (index / (props.data.length - 1)) * 100
    const y = 100 - point.successRate // Success rate is already a percentage
    return `${x},${y}`
  }).join(' ')
})

const chartCircles = computed(() => {
  if (!props.data.length) return []
  
  return props.data.map((point, index) => ({
    x: (index / (props.data.length - 1)) * 100,
    y: 100 - point.successRate,
    data: point,
    successRate: point.successRate
  }))
})

const areaPath = computed(() => {
  if (!props.data.length) return ''
  
  const points = props.data.map((point, index) => {
    const x = (index / (props.data.length - 1)) * 100
    const y = 100 - point.successRate
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

const overallSuccessRate = computed(() => {
  if (!props.data.length) return 0
  const totalSuccessful = props.data.reduce((sum, d) => sum + d.successful, 0)
  const totalAttempts = props.data.reduce((sum, d) => sum + d.total, 0)
  return totalAttempts > 0 ? (totalSuccessful / totalAttempts) * 100 : 0
})

const recentSuccessRate = computed(() => {
  if (!props.data.length) return 0
  const recentData = props.data.slice(-7) // Last 7 days
  const totalSuccessful = recentData.reduce((sum, d) => sum + d.successful, 0)
  const totalAttempts = recentData.reduce((sum, d) => sum + d.total, 0)
  return totalAttempts > 0 ? (totalSuccessful / totalAttempts) * 100 : 0
})

// Color and styling methods
function getPointColor(successRate: number): string {
  if (successRate >= 95) return '#10B981' // green - excellent
  if (successRate >= 85) return '#F59E0B' // yellow - good
  if (successRate >= 70) return '#F97316' // orange - warning
  return '#EF4444' // red - critical
}

function getSuccessRateColor(successRate?: number): string {
  if (!successRate && successRate !== 0) return 'text-gray-600 dark:text-gray-400'
  if (successRate >= 95) return 'text-green-600 dark:text-green-400'
  if (successRate >= 85) return 'text-yellow-600 dark:text-yellow-400'
  if (successRate >= 70) return 'text-orange-600 dark:text-orange-400'
  return 'text-red-600 dark:text-red-400'
}

function getSuccessRateStatus(): string {
  const rate = recentSuccessRate.value
  if (rate >= 95) return 'Excellent'
  if (rate >= 85) return 'Bon'
  if (rate >= 70) return 'Attention'
  return 'Critique'
}

function getSuccessRateStatusColor(): string {
  const rate = recentSuccessRate.value
  if (rate >= 95) return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
  if (rate >= 85) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
  if (rate >= 70) return 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400'
  return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
}

function getSuccessRateIcon() {
  const rate = recentSuccessRate.value
  if (rate >= 95) return CheckCircleIcon
  if (rate >= 85) return CheckIcon
  if (rate >= 70) return ExclamationIcon
  return XCircleIcon
}

// Tooltip methods
function showTooltip(event: MouseEvent, data: SuccessRateDataPoint, index: number) {
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

const CheckCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}

const XCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}
</script>