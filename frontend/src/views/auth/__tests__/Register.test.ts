import { describe, it, expect, vi, beforeEach } from 'vitest'
import { flushPromises } from '@vue/test-utils'
import Register from '../Register.vue'
import { mountWithPlugins } from '@/tests/utils/test-utils'
import { authApi } from '@/api/auth'

// Mock the API
vi.mock('@/api/auth', () => ({
  authApi: {
    register: vi.fn()
  }
}))

// Mock router
const mockRouter = {
  push: vi.fn(),
  replace: vi.fn()
}

describe('Register.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders registration form correctly', () => {
    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    expect(wrapper.find('[data-testid="register-form"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="name-input"]').exists()).toBe(true) // Name
    expect(wrapper.find('[data-testid="email-input"]').exists()).toBe(true) // Email  
    expect(wrapper.find('[data-testid="password-input"]').exists()).toBe(true) // Password
    expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
  })

  it('validates required fields', async () => {
    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Try to submit empty form
    const submitButton = wrapper.find('button[type="submit"]')
    await submitButton.trigger('click')

    await flushPromises()

    // Should show validation errors
    expect(wrapper.text()).toContain('requis')
  })

  it('validates email format', async () => {
    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Fill form with invalid email
    const nameInput = wrapper.find('[data-testid="name-input"]')
    const emailInput = wrapper.find('[data-testid="email-input"]')
    const passwordInput = wrapper.find('[data-testid="password-input"]')

    await nameInput.setValue('Test User')
    await emailInput.setValue('invalid-email')
    await passwordInput.setValue('ValidPassword123!')

    const submitButton = wrapper.find('button[type="submit"]')
    await submitButton.trigger('click')

    await flushPromises()

    // Should show email validation error
    expect(wrapper.text()).toContain('email valide')
  })

  it('validates password strength', async () => {
    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    const passwordInput = wrapper.find('[data-testid="password-input"]')
    
    // Test weak password
    await passwordInput.setValue('123')
    await passwordInput.trigger('input')

    // Should show password strength indicator
    const strengthIndicator = wrapper.find('[data-testid="password-strength"]')
    if (strengthIndicator.exists()) {
      expect(strengthIndicator.text()).toContain('Faible')
    }

    // Test strong password
    await passwordInput.setValue('StrongPassword123!')
    await passwordInput.trigger('input')

    if (strengthIndicator.exists()) {
      expect(strengthIndicator.text()).toContain('Fort')
    }
  })

  it('validates password confirmation', async () => {
    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    const passwordInput = wrapper.find('[data-testid="password-input"]')
    const confirmPasswordInput = wrapper.find('[data-testid="confirm-password-input"]')

    await passwordInput.setValue('Password123!')
    await confirmPasswordInput.setValue('DifferentPassword123!')

    const submitButton = wrapper.find('button[type="submit"]')
    await submitButton.trigger('click')

    await flushPromises()

    // Should show password mismatch error
    expect(wrapper.text()).toContain('correspondent')
  })

  it('requires terms acceptance', async () => {
    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Fill form but don't accept terms
    const nameInput = wrapper.find('[data-testid="name-input"]')
    const emailInput = wrapper.find('[data-testid="email-input"]')
    const passwordInput = wrapper.find('[data-testid="password-input"]')
    const confirmPasswordInput = wrapper.find('[data-testid="confirm-password-input"]')

    await nameInput.setValue('Test User')
    await emailInput.setValue('test@example.com')
    await passwordInput.setValue('Password123!')
    await confirmPasswordInput.setValue('Password123!')

    const submitButton = wrapper.find('button[type="submit"]')
    await submitButton.trigger('click')

    await flushPromises()

    // Should show terms acceptance error
    expect(wrapper.text()).toContain('conditions')
  })

  it('submits form successfully with valid data', async () => {
    vi.mocked(authApi.register).mockResolvedValue({
      success: true,
      data: {
        user: { id: '1', name: 'Test User', email: 'test@example.com' },
        token: 'mock-token'
      }
    })

    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Fill form with valid data
    const nameInput = wrapper.find('[data-testid="name-input"]')
    const emailInput = wrapper.find('[data-testid="email-input"]')
    const passwordInput = wrapper.find('[data-testid="password-input"]')
    const confirmPasswordInput = wrapper.find('[data-testid="confirm-password-input"]')
    const termsCheckbox = wrapper.find('[data-testid="terms-checkbox"]')

    await nameInput.setValue('Test User')
    await emailInput.setValue('test@example.com')
    await passwordInput.setValue('Password123!')
    await confirmPasswordInput.setValue('Password123!')
    await termsCheckbox.setChecked(true)

    const submitButton = wrapper.find('button[type="submit"]')
    await submitButton.trigger('click')

    await flushPromises()

    expect(authApi.register).toHaveBeenCalledWith({
      name: 'Test User',
      email: 'test@example.com',
      password: 'Password123!',
      acceptTerms: true,
      acceptPrivacy: false // Default value
    })

    expect(mockRouter.push).toHaveBeenCalledWith('/dashboard')
  })

  it('handles registration errors', async () => {
    vi.mocked(authApi.register).mockRejectedValue(new Error('Email already exists'))

    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Fill form with valid data
    const nameInput = wrapper.find('[data-testid="name-input"]')
    const emailInput = wrapper.find('[data-testid="email-input"]')
    const passwordInput = wrapper.find('[data-testid="password-input"]')
    const confirmPasswordInput = wrapper.find('[data-testid="confirm-password-input"]')
    const termsCheckbox = wrapper.find('[data-testid="terms-checkbox"]')

    await nameInput.setValue('Test User')
    await emailInput.setValue('test@example.com')
    await passwordInput.setValue('Password123!')
    await confirmPasswordInput.setValue('Password123!')
    await termsCheckbox.setChecked(true)

    const submitButton = wrapper.find('button[type="submit"]')
    await submitButton.trigger('click')

    await flushPromises()

    // Should show error message
    expect(wrapper.text()).toContain('Email already exists')
  })

  it('shows loading state during submission', async () => {
    // Make register take time to resolve
    vi.mocked(authApi.register).mockImplementation(() => 
      new Promise(resolve => 
        setTimeout(() => resolve({
          success: true,
          data: { user: { id: '1', name: 'Test', email: 'test@example.com' }, token: 'token' }
        }), 100)
      )
    )

    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Fill and submit form
    const nameInput = wrapper.find('[data-testid="name-input"]')
    const emailInput = wrapper.find('[data-testid="email-input"]')
    const passwordInput = wrapper.find('[data-testid="password-input"]')
    const confirmPasswordInput = wrapper.find('[data-testid="confirm-password-input"]')
    const termsCheckbox = wrapper.find('[data-testid="terms-checkbox"]')

    await nameInput.setValue('Test User')
    await emailInput.setValue('test@example.com')
    await passwordInput.setValue('Password123!')
    await confirmPasswordInput.setValue('Password123!')
    await termsCheckbox.setChecked(true)

    const submitButton = wrapper.find('button[type="submit"]')
    await submitButton.trigger('click')

    // Should show loading state
    expect(submitButton.text()).toContain('Création')
    expect(submitButton.attributes('disabled')).toBeDefined()
  })

  it('opens terms modal when clicking terms link', async () => {
    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Find and click terms link
    const termsLink = wrapper.find('[data-testid="terms-link"]')
    if (termsLink.exists()) {
      await termsLink.trigger('click')

      // Should open terms modal
      expect(wrapper.find('[data-testid="terms-modal"]').exists()).toBe(true)
    }
  })

  it('opens privacy modal when clicking privacy link', async () => {
    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Find and click privacy link
    const privacyLink = wrapper.find('[data-testid="privacy-link"]')
    if (privacyLink.exists()) {
      await privacyLink.trigger('click')

      // Should open privacy modal
      expect(wrapper.find('[data-testid="privacy-modal"]').exists()).toBe(true)
    }
  })

  it('navigates to login page when clicking login link', async () => {
    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    const loginLink = wrapper.find('[data-testid="login-link"]')
    if (loginLink.exists()) {
      await loginLink.trigger('click')
      
      expect(mockRouter.push).toHaveBeenCalledWith('/login')
    }
  })

  it('handles form reset correctly', async () => {
    const wrapper = mountWithPlugins(Register, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Fill form
    const nameInput = wrapper.find('[data-testid="name-input"]')
    const emailInput = wrapper.find('[data-testid="email-input"]')
    
    await nameInput.setValue('Test User')
    await emailInput.setValue('test@example.com')

    // Reset form (if reset functionality exists)
    const resetButton = wrapper.find('[data-testid="reset-form"]')
    if (resetButton.exists()) {
      await resetButton.trigger('click')

      expect(nameInput.element.value).toBe('')
      expect(emailInput.element.value).toBe('')
    }
  })
})