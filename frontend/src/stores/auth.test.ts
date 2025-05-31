import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from './auth'
import { authApi } from '@/api/auth'
import { factories } from '@/tests/utils/test-utils'

// Mock the auth API
vi.mock('@/api/auth', () => ({
  authApi: {
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
    updateProfile: vi.fn(),
    changePassword: vi.fn()
  }
}))

// Mock router
const mockRouter = {
  push: vi.fn(),
  replace: vi.fn()
}

vi.mock('vue-router', async () => {
  const actual = await vi.importActual('vue-router')
  return {
    ...actual,
    useRouter: () => mockRouter
  }
})

describe('Auth Store', () => {
  let store: ReturnType<typeof useAuthStore>

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useAuthStore()
    vi.clearAllMocks()
    localStorage.clear()
  })

  describe('Initial State', () => {
    it('has correct initial state', () => {
      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(store.loading).toBe(false)
    })
  })

  describe('Login', () => {
    it('successfully logs in user', async () => {
      const mockUser = factories.user()
      const mockToken = 'mock-jwt-token'
      
      vi.mocked(authApi.login).mockResolvedValue({
        success: true,
        data: {
          user: mockUser,
          token: mockToken
        }
      })

      const credentials = { email: 'test@example.com', password: 'password' }
      await store.login(credentials)

      expect(authApi.login).toHaveBeenCalledWith(credentials)
      expect(store.user).toEqual(mockUser)
      expect(store.token).toBe(mockToken)
      expect(store.isAuthenticated).toBe(true)
      expect(localStorage.getItem('auth-token')).toBe(mockToken)
      expect(mockRouter.push).toHaveBeenCalledWith('/dashboard')
    })

    it('handles login error', async () => {
      vi.mocked(authApi.login).mockRejectedValue(new Error('Invalid credentials'))

      const credentials = { email: 'test@example.com', password: 'wrong' }
      
      await expect(store.login(credentials)).rejects.toThrow('Invalid credentials')
      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(localStorage.getItem('auth-token')).toBeNull()
    })

    it('sets loading state during login', async () => {
      vi.mocked(authApi.login).mockImplementation(() => 
        new Promise(resolve => setTimeout(() => resolve({
          success: true,
          data: { user: factories.user(), token: 'token' }
        }), 100))
      )

      const loginPromise = store.login({ email: 'test@example.com', password: 'password' })
      
      expect(store.loading).toBe(true)
      
      await loginPromise
      
      expect(store.loading).toBe(false)
    })
  })

  describe('Register', () => {
    it('successfully registers new user', async () => {
      const mockUser = factories.user()
      const mockToken = 'mock-jwt-token'
      
      vi.mocked(authApi.register).mockResolvedValue({
        success: true,
        data: {
          user: mockUser,
          token: mockToken
        }
      })

      const registerData = {
        name: 'Test User',
        email: 'test@example.com',
        password: 'password',
        confirmPassword: 'password'
      }
      
      await store.register(registerData)

      expect(authApi.register).toHaveBeenCalledWith(registerData)
      expect(store.user).toEqual(mockUser)
      expect(store.token).toBe(mockToken)
      expect(store.isAuthenticated).toBe(true)
      expect(mockRouter.push).toHaveBeenCalledWith('/dashboard')
    })
  })

  describe('Logout', () => {
    it('successfully logs out user', async () => {
      // Set up authenticated state
      store.user = factories.user()
      store.token = 'token'
      store.isAuthenticated = true
      localStorage.setItem('auth-token', 'token')

      vi.mocked(authApi.logout).mockResolvedValue({ success: true })

      await store.logout()

      expect(authApi.logout).toHaveBeenCalled()
      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(localStorage.getItem('auth-token')).toBeNull()
      expect(mockRouter.push).toHaveBeenCalledWith('/login')
    })

    it('clears state even if API call fails', async () => {
      store.user = factories.user()
      store.token = 'token'
      store.isAuthenticated = true

      vi.mocked(authApi.logout).mockRejectedValue(new Error('Network error'))

      await store.logout()

      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('Check Auth', () => {
    it('validates existing token and fetches user', async () => {
      const mockUser = factories.user()
      localStorage.setItem('auth-token', 'valid-token')
      
      vi.mocked(authApi.me).mockResolvedValue({
        success: true,
        data: mockUser
      })

      await store.checkAuth()

      expect(store.token).toBe('valid-token')
      expect(store.user).toEqual(mockUser)
      expect(store.isAuthenticated).toBe(true)
    })

    it('clears auth on invalid token', async () => {
      localStorage.setItem('auth-token', 'invalid-token')
      
      vi.mocked(authApi.me).mockRejectedValue(new Error('Unauthorized'))

      await store.checkAuth()

      expect(store.token).toBeNull()
      expect(store.user).toBeNull()
      expect(store.isAuthenticated).toBe(false)
      expect(localStorage.getItem('auth-token')).toBeNull()
    })

    it('does nothing when no token exists', async () => {
      await store.checkAuth()

      expect(authApi.me).not.toHaveBeenCalled()
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('Update Profile', () => {
    it('updates user profile successfully', async () => {
      const currentUser = factories.user()
      const updatedUser = { ...currentUser, name: 'Updated Name' }
      
      store.user = currentUser
      
      vi.mocked(authApi.updateProfile).mockResolvedValue({
        success: true,
        data: updatedUser
      })

      await store.updateProfile({ name: 'Updated Name' })

      expect(authApi.updateProfile).toHaveBeenCalledWith({ name: 'Updated Name' })
      expect(store.user).toEqual(updatedUser)
    })

    it('throws error when not authenticated', async () => {
      await expect(store.updateProfile({ name: 'Test' }))
        .rejects.toThrow('User not authenticated')
      
      expect(authApi.updateProfile).not.toHaveBeenCalled()
    })
  })

  describe('Change Password', () => {
    it('changes password successfully', async () => {
      store.user = factories.user()
      
      vi.mocked(authApi.changePassword).mockResolvedValue({ success: true })

      await store.changePassword('oldpass', 'newpass')

      expect(authApi.changePassword).toHaveBeenCalledWith('oldpass', 'newpass')
    })

    it('throws error when not authenticated', async () => {
      await expect(store.changePassword('old', 'new'))
        .rejects.toThrow('User not authenticated')
    })
  })

  describe('Getters', () => {
    it('isAdmin returns true for admin users', () => {
      store.user = factories.user({ role: 'admin' })
      expect(store.isAdmin).toBe(true)
    })

    it('isAdmin returns false for regular users', () => {
      store.user = factories.user({ role: 'user' })
      expect(store.isAdmin).toBe(false)
    })

    it('isAdmin returns false when no user', () => {
      store.user = null
      expect(store.isAdmin).toBe(false)
    })

    it('userDisplayName returns user name', () => {
      store.user = factories.user({ name: 'John Doe' })
      expect(store.userDisplayName).toBe('John Doe')
    })

    it('userDisplayName returns email when no name', () => {
      store.user = factories.user({ name: '', email: 'john@example.com' })
      expect(store.userDisplayName).toBe('john@example.com')
    })

    it('userDisplayName returns empty string when no user', () => {
      store.user = null
      expect(store.userDisplayName).toBe('')
    })
  })
})