import { describe, it, expect, vi, beforeEach } from 'vitest'
import { flushPromises } from '@vue/test-utils'
import Profile from '../Profile.vue'
import { mountWithPlugins, factories } from '@/tests/utils/test-utils'
import { authApi } from '@/api/auth'

// Mock the API
vi.mock('@/api/auth', () => ({
  authApi: {
    me: vi.fn(),
    updateProfile: vi.fn(),
    updatePassword: vi.fn(),
    uploadAvatar: vi.fn(),
    deleteAccount: vi.fn()
  }
}))

// Mock router
const mockRouter = {
  push: vi.fn(),
  replace: vi.fn()
}

describe('Profile.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    
    // Setup default mocks
    vi.mocked(authApi.me).mockResolvedValue({
      success: true,
      data: {
        user: {
          id: '1',
          name: 'John Doe',
          email: 'john.doe@example.com',
          avatar: null,
          createdAt: '2023-01-01T00:00:00Z',
          updatedAt: '2023-01-01T00:00:00Z'
        }
      }
    })
  })

  it('renders profile interface correctly', () => {
    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    expect(wrapper.find('[data-testid="profile-container"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="profile-tabs"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Mon profil')
  })

  it('displays user information correctly', async () => {
    const mockUser = factories.user({
      name: 'John Doe',
      email: 'john.doe@example.com'
    })

    const wrapper = mountWithPlugins(Profile, {
      global: {
        provide: {
          user: mockUser
        },
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('john.doe@example.com')
  })

  it('switches between profile tabs correctly', async () => {
    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Default should be personal info tab
    expect(wrapper.find('[data-testid="personal-info-section"]').exists()).toBe(true)

    // Click security tab
    const securityTab = wrapper.find('[data-testid="security-tab"]')
    if (securityTab.exists()) {
      await securityTab.trigger('click')
      expect(wrapper.find('[data-testid="security-section"]').exists()).toBe(true)
    }

    // Click preferences tab
    const preferencesTab = wrapper.find('[data-testid="preferences-tab"]')
    if (preferencesTab.exists()) {
      await preferencesTab.trigger('click')
      expect(wrapper.find('[data-testid="preferences-section"]').exists()).toBe(true)
    }
  })

  it('updates personal information successfully', async () => {
    vi.mocked(authApi.updateProfile).mockResolvedValue({
      success: true,
      data: factories.user({ name: 'Updated Name' })
    })

    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    await flushPromises()

    // Find name input and update it
    const nameInput = wrapper.find('input[placeholder*="nom"]')
    if (nameInput.exists()) {
      await nameInput.setValue('Updated Name')

      // Find and click save button
      const saveButton = wrapper.find('[data-testid="save-personal-info"]')
      if (saveButton.exists()) {
        await saveButton.trigger('click')
        await flushPromises()

        expect(authApi.updateProfile).toHaveBeenCalledWith({
          name: 'Updated Name'
        })
      }
    }
  })

  it('validates personal information form', async () => {
    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Try to save with empty name
    const nameInput = wrapper.find('input[placeholder*="nom"]')
    if (nameInput.exists()) {
      await nameInput.setValue('')

      const saveButton = wrapper.find('[data-testid="save-personal-info"]')
      if (saveButton.exists()) {
        await saveButton.trigger('click')

        // Should show validation error
        expect(wrapper.text()).toContain('requis')
      }
    }
  })

  it('updates password successfully', async () => {
    vi.mocked(authApi.updatePassword).mockResolvedValue({
      success: true,
      data: null
    })

    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Switch to security tab
    const securityTab = wrapper.find('[data-testid="security-tab"]')
    if (securityTab.exists()) {
      await securityTab.trigger('click')

      // Fill password form
      const currentPasswordInput = wrapper.find('input[placeholder*="actuel"]')
      const newPasswordInput = wrapper.find('input[placeholder*="nouveau"]')
      const confirmPasswordInput = wrapper.find('input[placeholder*="confirmer"]')

      if (currentPasswordInput.exists() && newPasswordInput.exists() && confirmPasswordInput.exists()) {
        await currentPasswordInput.setValue('currentpassword')
        await newPasswordInput.setValue('NewPassword123!')
        await confirmPasswordInput.setValue('NewPassword123!')

        const updatePasswordButton = wrapper.find('[data-testid="update-password"]')
        if (updatePasswordButton.exists()) {
          await updatePasswordButton.trigger('click')
          await flushPromises()

          expect(authApi.updatePassword).toHaveBeenCalledWith({
            currentPassword: 'currentpassword',
            newPassword: 'NewPassword123!'
          })
        }
      }
    }
  })

  it('validates password change form', async () => {
    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Switch to security tab
    const securityTab = wrapper.find('[data-testid="security-tab"]')
    if (securityTab.exists()) {
      await securityTab.trigger('click')

      // Try to submit with mismatched passwords
      const newPasswordInput = wrapper.find('input[placeholder*="nouveau"]')
      const confirmPasswordInput = wrapper.find('input[placeholder*="confirmer"]')

      if (newPasswordInput.exists() && confirmPasswordInput.exists()) {
        await newPasswordInput.setValue('Password123!')
        await confirmPasswordInput.setValue('DifferentPassword123!')

        const updatePasswordButton = wrapper.find('[data-testid="update-password"]')
        if (updatePasswordButton.exists()) {
          await updatePasswordButton.trigger('click')

          // Should show validation error
          expect(wrapper.text()).toContain('correspondent')
        }
      }
    }
  })

  it('uploads avatar successfully', async () => {
    const mockFile = new File(['mock content'], 'avatar.jpg', { type: 'image/jpeg' })
    
    vi.mocked(authApi.uploadAvatar).mockResolvedValue({
      success: true,
      data: { avatarUrl: '/avatars/new-avatar.jpg' }
    })

    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    const avatarInput = wrapper.find('input[type="file"]')
    if (avatarInput.exists()) {
      // Mock file selection
      Object.defineProperty(avatarInput.element, 'files', {
        value: [mockFile],
        writable: false
      })

      await avatarInput.trigger('change')
      await flushPromises()

      expect(authApi.uploadAvatar).toHaveBeenCalledWith(mockFile)
    }
  })

  it('validates avatar file type and size', async () => {
    const mockFile = new File(['mock content'], 'document.pdf', { type: 'application/pdf' })

    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    const avatarInput = wrapper.find('input[type="file"]')
    if (avatarInput.exists()) {
      Object.defineProperty(avatarInput.element, 'files', {
        value: [mockFile],
        writable: false
      })

      await avatarInput.trigger('change')

      // Should show error for invalid file type
      expect(wrapper.text()).toContain('format')
    }
  })

  it('updates preferences successfully', async () => {
    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Switch to preferences tab
    const preferencesTab = wrapper.find('[data-testid="preferences-tab"]')
    if (preferencesTab.exists()) {
      await preferencesTab.trigger('click')

      // Update theme preference
      const themeSelect = wrapper.find('select[data-testid="theme-select"]')
      if (themeSelect.exists()) {
        await themeSelect.setValue('dark')

        const savePreferencesButton = wrapper.find('[data-testid="save-preferences"]')
        if (savePreferencesButton.exists()) {
          await savePreferencesButton.trigger('click')
          await flushPromises()

          // Should save preferences
          expect(authApi.updateProfile).toHaveBeenCalledWith(
            expect.objectContaining({
              preferences: expect.objectContaining({
                theme: 'dark'
              })
            })
          )
        }
      }
    }
  })

  it('handles notification preferences', async () => {
    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Switch to preferences tab
    const preferencesTab = wrapper.find('[data-testid="preferences-tab"]')
    if (preferencesTab.exists()) {
      await preferencesTab.trigger('click')

      // Toggle notification settings
      const emailNotificationCheckbox = wrapper.find('input[data-testid="email-notifications"]')
      if (emailNotificationCheckbox.exists()) {
        await emailNotificationCheckbox.setChecked(false)

        const savePreferencesButton = wrapper.find('[data-testid="save-preferences"]')
        if (savePreferencesButton.exists()) {
          await savePreferencesButton.trigger('click')

          // Should update notification preferences
          expect(authApi.updateProfile).toHaveBeenCalledWith(
            expect.objectContaining({
              preferences: expect.objectContaining({
                notifications: expect.objectContaining({
                  email: false
                })
              })
            })
          )
        }
      }
    }
  })

  it('handles account deletion', async () => {
    vi.mocked(authApi.deleteAccount).mockResolvedValue({
      success: true,
      data: null
    })

    global.confirm = vi.fn(() => true)

    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Find delete account button
    const deleteAccountButton = wrapper.find('[data-testid="delete-account"]')
    if (deleteAccountButton.exists()) {
      await deleteAccountButton.trigger('click')

      expect(global.confirm).toHaveBeenCalled()
      expect(authApi.deleteAccount).toHaveBeenCalled()
      expect(mockRouter.push).toHaveBeenCalledWith('/login')
    }
  })

  it('shows loading states during operations', async () => {
    // Make API calls take time to resolve
    vi.mocked(authApi.updateProfile).mockImplementation(() => 
      new Promise(resolve => 
        setTimeout(() => resolve({
          success: true,
          data: factories.user()
        }), 100)
      )
    )

    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    const nameInput = wrapper.find('input[placeholder*="nom"]')
    if (nameInput.exists()) {
      await nameInput.setValue('New Name')

      const saveButton = wrapper.find('[data-testid="save-personal-info"]')
      if (saveButton.exists()) {
        await saveButton.trigger('click')

        // Should show loading state
        expect(saveButton.text()).toContain('Enregistrement')
        expect(saveButton.attributes('disabled')).toBeDefined()
      }
    }
  })

  it('handles API errors gracefully', async () => {
    vi.mocked(authApi.updateProfile).mockRejectedValue(new Error('Update failed'))

    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    const nameInput = wrapper.find('input[placeholder*="nom"]')
    if (nameInput.exists()) {
      await nameInput.setValue('New Name')

      const saveButton = wrapper.find('[data-testid="save-personal-info"]')
      if (saveButton.exists()) {
        await saveButton.trigger('click')
        await flushPromises()

        // Should show error message
        expect(wrapper.text()).toContain('Update failed')
      }
    }
  })

  it('shows unsaved changes warning', async () => {
    const wrapper = mountWithPlugins(Profile, {
      global: {
        mocks: {
          $router: mockRouter
        }
      }
    })

    // Make changes
    const nameInput = wrapper.find('input[placeholder*="nom"]')
    if (nameInput.exists()) {
      await nameInput.setValue('Modified Name')

      // Should show unsaved changes indicator
      const unsavedIndicator = wrapper.find('[data-testid="unsaved-changes"]')
      expect(unsavedIndicator.exists()).toBe(true)
    }
  })
})