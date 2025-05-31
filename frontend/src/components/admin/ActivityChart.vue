<template>
  <div class="w-full h-80">
    <div v-if="loading" class="animate-pulse h-full">
      <div class="flex justify-between items-end h-full space-x-2">
        <div v-for="i in 12" :key="i" class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-t" :style="{ height: Math.random() * 80 + 20 + '%' }"></div>
      </div>
    </div>
    
    <div v-else-if="!chartData?.length" class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        <p>Aucune donnée disponible</p>
      </div>
    </div>
    
    <div v-else class="h-full">
      <!-- Graphique simple avec barres -->
      <div class="relative h-full">
        <!-- Axe Y (valeurs) -->
        <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-xs text-gray-500 dark:text-gray-400 pr-2">
          <span>{{ formatValue(maxValue) }}</span>
          <span>{{ formatValue(maxValue * 0.75) }}</span>
          <span>{{ formatValue(maxValue * 0.5) }}</span>
          <span>{{ formatValue(maxValue * 0.25) }}</span>
          <span>0</span>
        </div>
        
        <!-- Zone du graphique -->
        <div class="ml-12 h-full">
          <div class="flex items-end justify-between h-full space-x-1 pb-8">
            <div
              v-for="(point, index) in chartData"
              :key="index"
              class="relative flex-1 group cursor-pointer"
              @mouseenter="hoveredIndex = index"
              @mouseleave="hoveredIndex = null"
            >
              <!-- Barre -->
              <div
                class="bg-blue-500 hover:bg-blue-600 dark:bg-blue-400 dark:hover:bg-blue-300 rounded-t transition-all duration-200 w-full"
                :style="{ height: getBarHeight(point.value) + '%' }"
              ></div>
              
              <!-- Tooltip -->
              <div
                v-if="hoveredIndex === index"
                class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded py-1 px-2 whitespace-nowrap z-10"
              >
                <div class="font-medium">{{ formatValue(point.value) }}</div>
                <div class="text-gray-300">{{ formatDate(point.date) }}</div>
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
              </div>
            </div>
          </div>
          
          <!-- Axe X (dates) -->
          <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
            <span v-for="(point, index) in dateLabels" :key="index">
              {{ point }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface ActivityDataPoint {
  date: string
  users?: number
  transcriptions?: number
  revenue?: number
  storage?: number
  [key: string]: any
}

interface Props {
  data: ActivityDataPoint[]
  metric: string
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const hoveredIndex = ref<number | null>(null)

const chartData = computed(() => {
  if (!props.data?.length) return []
  
  return props.data.map(point => ({
    date: point.date,
    value: point[props.metric] || 0
  }))
})

const maxValue = computed(() => {
  if (!chartData.value?.length) return 100
  
  const max = Math.max(...chartData.value.map(d => d.value))
  // Ajouter une marge de 10% pour l'affichage
  return max * 1.1
})

const dateLabels = computed(() => {
  if (!chartData.value?.length) return []
  
  // Afficher seulement quelques dates pour éviter l'encombrement
  const step = Math.max(1, Math.floor(chartData.value.length / 6))
  return chartData.value
    .filter((_, index) => index % step === 0)
    .map(point => formatDate(point.date, true))
})

function getBarHeight(value: number): number {
  if (!maxValue.value) return 0
  return Math.max(2, (value / maxValue.value) * 100)
}

function formatValue(value: number): string {
  if (props.metric === 'revenue') {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR',
      maximumFractionDigits: 0
    }).format(value)
  }
  
  if (props.metric === 'storage') {
    if (value >= 1000) {
      return (value / 1000).toFixed(1) + 'TB'
    }
    return value.toFixed(1) + 'GB'
  }
  
  if (value >= 1000) {
    return (value / 1000).toFixed(1) + 'k'
  }
  
  return Math.round(value).toString()
}

function formatDate(dateStr: string, short: boolean = false): string {
  const date = new Date(dateStr)
  
  if (short) {
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit'
    })
  }
  
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })
}
</script>

<script lang="ts">
export default {
  name: 'ActivityChart'
}
</script>