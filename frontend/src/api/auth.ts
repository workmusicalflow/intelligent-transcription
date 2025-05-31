import api, { setAuthToken, clearAuthToken } from './client'
import type { 
  User, 
  LoginCredentials, 
  RegisterData, 
  ApiResponse 
} from '@/types'

export interface AuthResponse {
  user: User
  token: string
}

export const authApi = {
  // Login
  login: async (credentials: LoginCredentials): Promise<ApiResponse<AuthResponse>> => {
    return api.post<AuthResponse>('/auth/login', credentials)
  },

  // Register
  register: async (data: RegisterData): Promise<ApiResponse<AuthResponse>> => {
    return api.post<AuthResponse>('/auth/register', data)
  },

  // Logout
  logout: async (): Promise<ApiResponse> => {
    return api.post('/auth/logout')
  },

  // Get current user
  me: async (): Promise<ApiResponse<User>> => {
    return api.get<User>('/auth/me')
  },

  // Update profile
  updateProfile: async (data: Partial<User>): Promise<ApiResponse<User>> => {
    return api.put<User>('/auth/profile', data)
  },

  // Change password
  changePassword: async (currentPassword: string, newPassword: string): Promise<ApiResponse> => {
    return api.post('/auth/change-password', {
      current_password: currentPassword,
      new_password: newPassword
    })
  },

  // Request password reset
  requestPasswordReset: async (email: string): Promise<ApiResponse> => {
    return api.post('/auth/password-reset/request', { email })
  },

  // Reset password
  resetPassword: async (token: string, password: string): Promise<ApiResponse> => {
    return api.post('/auth/password-reset/confirm', { token, password })
  },

  // Verify email
  verifyEmail: async (token: string): Promise<ApiResponse> => {
    return api.post('/auth/verify-email', { token })
  },

  // Resend verification email
  resendVerification: async (): Promise<ApiResponse> => {
    return api.post('/auth/resend-verification')
  },

  // Refresh token
  refreshToken: async (): Promise<ApiResponse<AuthResponse>> => {
    return api.post<AuthResponse>('/auth/refresh')
  },

  // Update user preferences
  updatePreferences: async (preferences: Partial<User['preferences']>): Promise<ApiResponse<User>> => {
    return api.patch<User>('/auth/preferences', preferences)
  },

  // Delete account
  deleteAccount: async (password: string): Promise<ApiResponse> => {
    return api.post('/auth/delete-account', { password })
  },

  // Get user sessions
  getSessions: async (): Promise<ApiResponse<any[]>> => {
    return api.get('/auth/sessions')
  },

  // Revoke session
  revokeSession: async (sessionId: string): Promise<ApiResponse> => {
    return api.delete(`/auth/sessions/${sessionId}`)
  },

  // Set auth token
  setAuthToken,

  // Clear auth token
  clearAuthToken
}