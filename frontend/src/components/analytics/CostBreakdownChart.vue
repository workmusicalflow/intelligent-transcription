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
        <!-- Donut Chart -->
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
            
            <!-- Donut segments -->
            <path
              v-for="(segment, index) in donutSegments"
              :key="index"
              :d="segment.path"
              :fill="segment.color"
              :stroke="segment.color"
              stroke-width="2"
              class="cursor-pointer hover:opacity-80 transition-opacity"
              @mouseenter="showTooltip($event, segment.data)"
              @mouseleave="hideTooltip"
            />
            
            <!-- Inner circle to create donut effect -->
            <circle
              cx="100"
              cy="100"
              r="50"
              fill="currentColor"
              class="text-white dark:text-gray-800"
            />
          </svg>
          
          <!-- Center text -->
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center">
              <div class="text-xl font-bold text-gray-900 dark:text-white">
                {{ formatCurrency(totalCost) }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                Total
              </div>
            </div>
          </div>
        </div>
        
        <!-- Legend and details -->
        <div class="space-y-4">
          <!-- Service breakdown -->
          <div class="space-y-3">
            <div
              v-for="(item, index) in data"
              :key="index"
              class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
              @mouseenter="highlightSegment(index)"
              @mouseleave="unhighlightSegment"
            >
              <div class="flex items-center space-x-3 flex-1">
                <div 
                  class="w-4 h-4 rounded-full flex-shrink-0"
                  :style="{ backgroundColor: item.color }"
                ></div>
                <div class="min-w-0 flex-1">
                  <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ item.service }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ item.usage }} utilisations
                  </div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ formatCurrency(item.cost) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ item.percentage.toFixed(1) }}%
                </div>
              </div>
            </div>
          </div>
          
          <!-- Cost optimization suggestions -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
              Optimisations suggérées
            </h4>
            <div class="space-y-2">
              <div
                v-for="suggestion in optimizationSuggestions"
                :key="suggestion.id"
                class="flex items-start space-x-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg"
              >
                <LightBulbIcon class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                <div class="text-xs text-blue-700 dark:text-blue-300">
                  <div class="font-medium">{{ suggestion.title }}</div>
                  <div class="mt-1">{{ suggestion.description }}</div>
                  <div v-if="suggestion.savings" class="mt-1 font-medium">
                    Économie: {{ formatCurrency(suggestion.savings) }}/mois
                  </div>
                </div>
              </div>
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
          {{ tooltip.data?.service }}
        </div>
        <div class="text-gray-600 dark:text-gray-400">
          Coût: {{ formatCurrency(tooltip.data?.cost || 0) }}
        </div>
        <div class="text-gray-600 dark:text-gray-400">
          Part: {{ tooltip.data?.percentage.toFixed(1) }}%
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          {{ tooltip.data?.usage }} utilisations
        </div>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-if="!loading && !error && data.length === 0" class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
      <div class="text-center">
        <ChartPieIcon class="h-12 w-12 mx-auto mb-2" />
        <p>Aucune donnée de coût disponible</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

interface CostBreakdownItem {
  service: string
  cost: number
  percentage: number
  usage: number
  color: string
}

interface OptimizationSuggestion {
  id: string
  title: string
  description: string
  savings?: number
  service?: string
}

interface Props {
  data: CostBreakdownItem[]
  loading?: boolean
  error?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: false
})

// State
const tooltip = ref({
  show: false,
  x: 0,
  y: 0,
  data: null as CostBreakdownItem | null
})

const highlightedIndex = ref<number | null>(null)

// Computed
const totalCost = computed(() => {
  return props.data.reduce((sum, item) => sum + item.cost, 0)
})

const donutSegments = computed(() => {
  if (!props.data.length) return []
  
  let currentAngle = 0
  const outerRadius = 80
  const innerRadius = 50
  const centerX = 100
  const centerY = 100
  
  return props.data.map((item, index) => {
    const percentage = item.percentage / 100
    const angle = percentage * 2 * Math.PI
    
    const startAngle = currentAngle
    const endAngle = currentAngle + angle
    
    const x1 = centerX + outerRadius * Math.cos(startAngle)
    const y1 = centerY + outerRadius * Math.sin(startAngle)
    const x2 = centerX + outerRadius * Math.cos(endAngle)
    const y2 = centerY + outerRadius * Math.sin(endAngle)
    
    const x3 = centerX + innerRadius * Math.cos(endAngle)
    const y3 = centerY + innerRadius * Math.sin(endAngle)
    const x4 = centerX + innerRadius * Math.cos(startAngle)
    const y4 = centerY + innerRadius * Math.sin(startAngle)
    
    const largeArc = angle > Math.PI ? 1 : 0
    
    const path = [
      `M ${x1} ${y1}`,
      `A ${outerRadius} ${outerRadius} 0 ${largeArc} 1 ${x2} ${y2}`,
      `L ${x3} ${y3}`,
      `A ${innerRadius} ${innerRadius} 0 ${largeArc} 0 ${x4} ${y4}`,
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

const optimizationSuggestions = computed((): OptimizationSuggestion[] => {
  const suggestions: OptimizationSuggestion[] = []
  
  // Generate suggestions based on cost data
  props.data.forEach(item => {
    if (item.service === 'OpenAI Whisper' && item.cost > totalCost.value * 0.4) {
      suggestions.push({
        id: 'whisper-optimization',
        title: 'Optimiser Whisper',
        description: 'Considérez le preprocessing audio pour réduire les coûts',
        savings: item.cost * 0.15,
        service: item.service
      })
    }
    
    if (item.service === 'Stockage' && item.cost > 20) {
      suggestions.push({
        id: 'storage-cleanup',
        title: 'Nettoyage du stockage',
        description: 'Supprimez les fichiers temporaires anciens',
        savings: item.cost * 0.3,
        service: item.service
      })
    }
    
    if (item.service === 'Cache Redis' && item.usage < 100) {
      suggestions.push({
        id: 'redis-scaling',
        title: 'Réduire le cache Redis',
        description: 'L\'utilisation du cache est faible, réduisez la taille',
        savings: item.cost * 0.5,
        service: item.service
      })
    }
  })
  
  // Add general suggestions if no specific ones
  if (suggestions.length === 0) {
    suggestions.push({
      id: 'general-monitoring',
      title: 'Surveillance continue',
      description: 'Configurez des alertes de coût pour un meilleur contrôle'
    })
  }
  
  return suggestions.slice(0, 3) // Limit to 3 suggestions
})

// Methods
function showTooltip(event: MouseEvent, data: CostBreakdownItem) {
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

function formatCurrency(amount: number): string {
  return `${amount.toFixed(2)}€`
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

const LightBulbIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
    </svg>
  `
}
</script>