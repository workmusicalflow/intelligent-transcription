import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import type { User, LoginCredentials, RegisterData, AuthState } from '@/types'
import { authApi } from '@api/auth'
import { useUIStore } from './ui'

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter()
  const uiStore = useUIStore()
  
  // State
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth-token'))
  const isLoading = ref(false)
  const loginAttempts = ref(0)
  const maxLoginAttempts = 5
  
  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isPremium = computed(() => user.value?.role === 'premium' || isAdmin.value)
  const isLocked = computed(() => loginAttempts.value >= maxLoginAttempts)
  
  // Actions
  const setUser = (userData: User | null) => {
    user.value = userData
  }
  
  const setToken = (tokenValue: string | null) => {
    token.value = tokenValue
    if (tokenValue) {
      localStorage.setItem('auth-token', tokenValue)
      // Set default authorization header for API calls
      authApi.setAuthToken(tokenValue)
    } else {
      localStorage.removeItem('auth-token')
      authApi.clearAuthToken()
    }
  }
  
  const login = async (credentials: LoginCredentials) => {
    if (isLocked.value) {
      throw new Error('Trop de tentatives de connexion. Veuillez attendre.')
    }
    
    try {
      isLoading.value = true
      
      const response = await authApi.login(credentials)
      
      if (response.success && response.data) {
        setToken(response.data.token)
        setUser(response.data.user)
        
        // Reset login attempts
        loginAttempts.value = 0
        
        // Show success notification
        uiStore.showNotification({
          type: 'success',
          title: 'Connexion réussie',
          message: `Bienvenue ${response.data.user.name}!`
        })
        
        // Redirect to dashboard
        await router.push('/dashboard')
        
        return response.data
      } else {
        throw new Error(response.message || 'Échec de la connexion')
      }
    } catch (error) {
      loginAttempts.value++
      
      const message = error instanceof Error ? error.message : 'Erreur de connexion'
      
      uiStore.showNotification({
        type: 'error',
        title: 'Erreur de connexion',
        message
      })
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  const register = async (data: RegisterData) => {
    try {
      isLoading.value = true
      
      const response = await authApi.register(data)
      
      if (response.success && response.data) {
        setToken(response.data.token)
        setUser(response.data.user)
        
        uiStore.showNotification({
          type: 'success',
          title: 'Inscription réussie',
          message: 'Votre compte a été créé avec succès!'
        })
        
        await router.push('/dashboard')
        
        return response.data
      } else {
        throw new Error(response.message || 'Échec de l\'inscription')
      }
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Erreur d\'inscription'
      
      uiStore.showNotification({
        type: 'error',
        title: 'Erreur d\'inscription',
        message
      })
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  const logout = async () => {
    try {
      // Call logout API endpoint
      await authApi.logout()
    } catch (error) {
      console.error('Logout API call failed:', error)
    } finally {
      // Clear local state regardless of API call result
      setToken(null)
      setUser(null)
      
      uiStore.showNotification({
        type: 'info',
        title: 'Déconnexion',
        message: 'Vous avez été déconnecté avec succès'
      })
      
      await router.push('/login')
    }
  }
  
  const checkAuth = async () => {
    if (!token.value) {
      return false
    }
    
    try {
      isLoading.value = true
      
      const response = await authApi.me()
      
      if (response.success && response.data) {
        setUser(response.data)
        return true
      } else {
        // Token is invalid
        await logout()
        return false
      }
    } catch (error) {
      console.error('Auth check failed:', error)
      await logout()
      return false
    } finally {
      isLoading.value = false
    }
  }
  
  const updateProfile = async (userData: Partial<User>) => {
    try {
      isLoading.value = true
      
      const response = await authApi.updateProfile(userData)
      
      if (response.success && response.data) {
        setUser(response.data)
        
        uiStore.showNotification({
          type: 'success',
          title: 'Profil mis à jour',
          message: 'Vos informations ont été mises à jour avec succès'
        })
        
        return response.data
      } else {
        throw new Error(response.message || 'Échec de la mise à jour')
      }
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Erreur de mise à jour'
      
      uiStore.showNotification({
        type: 'error',
        title: 'Erreur de mise à jour',
        message
      })
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  const changePassword = async (currentPassword: string, newPassword: string) => {
    try {
      isLoading.value = true
      
      const response = await authApi.changePassword(currentPassword, newPassword)
      
      if (response.success) {
        uiStore.showNotification({
          type: 'success',
          title: 'Mot de passe modifié',
          message: 'Votre mot de passe a été modifié avec succès'
        })
        
        return true
      } else {
        throw new Error(response.message || 'Échec du changement de mot de passe')
      }
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Erreur de changement de mot de passe'
      
      uiStore.showNotification({
        type: 'error',
        title: 'Erreur',
        message
      })
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  const requestPasswordReset = async (email: string) => {
    try {
      isLoading.value = true
      
      const response = await authApi.requestPasswordReset(email)
      
      if (response.success) {
        uiStore.showNotification({
          type: 'success',
          title: 'Email envoyé',
          message: 'Un email de réinitialisation vous a été envoyé'
        })
        
        return true
      } else {
        throw new Error(response.message || 'Échec de l\'envoi de l\'email')
      }
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Erreur d\'envoi'
      
      uiStore.showNotification({
        type: 'error',
        title: 'Erreur',
        message
      })
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  return {
    // State
    user,
    token,
    isLoading,
    loginAttempts,
    
    // Getters
    isAuthenticated,
    isAdmin,
    isPremium,
    isLocked,
    
    // Actions
    login,
    register,
    logout,
    checkAuth,
    updateProfile,
    changePassword,
    requestPasswordReset,
    setUser,
    setToken
  }
})