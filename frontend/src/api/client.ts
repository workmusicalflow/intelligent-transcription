import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios'
import type { ApiResponse } from '@/types'

// Create axios instance with default config
const createApiClient = (): AxiosInstance => {
  const client = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
    timeout: 30000,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
  })

  // Request interceptor
  client.interceptors.request.use(
    (config) => {
      // Add auth token if available
      const token = localStorage.getItem('auth-token')
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }

      // Add request timestamp
      config.metadata = { startTime: new Date() }

      console.log(`🚀 ${config.method?.toUpperCase()} ${config.url}`, {
        params: config.params,
        data: config.data
      })

      return config
    },
    (error) => {
      console.error('Request error:', error)
      return Promise.reject(error)
    }
  )

  // Response interceptor
  client.interceptors.response.use(
    (response: AxiosResponse) => {
      const duration = response.config.metadata?.startTime ? 
        new Date().getTime() - response.config.metadata.startTime.getTime() : 0
      
      console.log(`✅ ${response.config.method?.toUpperCase()} ${response.config.url} (${duration}ms)`, {
        status: response.status,
        data: response.data
      })

      return response
    },
    (error) => {
      const duration = error.config?.metadata?.startTime ? 
        new Date().getTime() - error.config.metadata.startTime.getTime() : 0
      
      console.error(`❌ ${error.config?.method?.toUpperCase()} ${error.config?.url} (${duration}ms)`, {
        status: error.response?.status,
        message: error.message,
        data: error.response?.data
      })

      // Handle specific error cases
      if (error.response?.status === 401) {
        // Redirect to login
        localStorage.removeItem('auth-token')
        window.location.href = '/login'
      }

      if (error.response?.status === 403) {
        // Show forbidden error
        console.error('Access forbidden')
      }

      if (error.response?.status >= 500) {
        // Show server error notification
        console.error('Server error occurred')
      }

      return Promise.reject(error)
    }
  )

  return client
}

// Create the main API client
export const apiClient = createApiClient()

// Generic API methods
export const api = {
  get: async <T = any>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> => {
    try {
      const response = await apiClient.get(url, config)
      return response.data
    } catch (error) {
      throw handleApiError(error)
    }
  },

  post: async <T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> => {
    try {
      const response = await apiClient.post(url, data, config)
      return response.data
    } catch (error) {
      throw handleApiError(error)
    }
  },

  put: async <T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> => {
    try {
      const response = await apiClient.put(url, data, config)
      return response.data
    } catch (error) {
      throw handleApiError(error)
    }
  },

  patch: async <T = any>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> => {
    try {
      const response = await apiClient.patch(url, data, config)
      return response.data
    } catch (error) {
      throw handleApiError(error)
    }
  },

  delete: async <T = any>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> => {
    try {
      const response = await apiClient.delete(url, config)
      return response.data
    } catch (error) {
      throw handleApiError(error)
    }
  },

  // File upload with progress
  upload: async <T = any>(
    url: string, 
    file: File, 
    onProgress?: (progressEvent: any) => void
  ): Promise<ApiResponse<T>> => {
    const formData = new FormData()
    formData.append('file', file)

    try {
      const response = await apiClient.post(url, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        },
        onUploadProgress: onProgress
      })
      return response.data
    } catch (error) {
      throw handleApiError(error)
    }
  }
}

// Error handling
const handleApiError = (error: any): Error => {
  if (error.response) {
    // Server responded with error status
    const { data, status } = error.response
    
    if (data?.message) {
      return new Error(data.message)
    }
    
    switch (status) {
      case 400:
        return new Error('Requête invalide')
      case 401:
        return new Error('Non autorisé')
      case 403:
        return new Error('Accès interdit')
      case 404:
        return new Error('Ressource non trouvée')
      case 409:
        return new Error('Conflit de données')
      case 422:
        return new Error('Données invalides')
      case 429:
        return new Error('Trop de requêtes')
      case 500:
        return new Error('Erreur serveur interne')
      case 503:
        return new Error('Service temporairement indisponible')
      default:
        return new Error(`Erreur HTTP ${status}`)
    }
  } else if (error.request) {
    // Network error
    return new Error('Erreur de connexion réseau')
  } else {
    // Something else happened
    return new Error(error.message || 'Erreur inconnue')
  }
}

// Auth token management
export const setAuthToken = (token: string) => {
  apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

export const clearAuthToken = () => {
  delete apiClient.defaults.headers.common['Authorization']
}

// Add request/response timing
declare module 'axios' {
  interface AxiosRequestConfig {
    metadata?: {
      startTime: Date
    }
  }
}

export default api