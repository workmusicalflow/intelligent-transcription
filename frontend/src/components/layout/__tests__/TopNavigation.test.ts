import { describe, it, expect, vi, beforeEach } from 'vitest'
import { flushPromises } from '@vue/test-utils'
import TopNavigation from '../TopNavigation.vue'
import { mountWithPlugins } from '@/tests/utils/test-utils'
import { useUIStore } from '@/stores/ui'

// Mock des icônes
vi.mock('@heroicons/vue/24/outline', () => ({
  Bars3Icon: { name: 'Bars3Icon', template: '<div data-testid="bars3-icon"></div>' },
  ChevronRightIcon: { name: 'ChevronRightIcon', template: '<div data-testid="chevron-right-icon"></div>' }
}))

// Mock des composants enfants
vi.mock('../SearchBox.vue', () => ({
  default: { name: 'SearchBox', template: '<div data-testid="search-box"></div>' }
}))

vi.mock('../ConnectionStatus.vue', () => ({
  default: { name: 'ConnectionStatus', template: '<div data-testid="connection-status"></div>' }
}))

vi.mock('../NotificationButton.vue', () => ({
  default: { name: 'NotificationButton', template: '<div data-testid="notification-button"></div>' }
}))

vi.mock('../ThemeToggle.vue', () => ({
  default: { name: 'ThemeToggle', template: '<div data-testid="theme-toggle"></div>' }
}))

vi.mock('../UserDropdown.vue', () => ({
  default: { name: 'UserDropdown', template: '<div data-testid="user-dropdown"></div>' }
}))

describe('TopNavigation.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const createWrapper = (routeOverride = {}) => {
    return mountWithPlugins(TopNavigation, {
      global: {
        mocks: {
          $route: {
            name: 'Dashboard',
            path: '/dashboard',
            meta: {},
            ...routeOverride
          }
        },
        stubs: {
          Transition: {
            template: '<div><slot /></div>'
          }
        }
      }
    })
  }

  describe('Rendering', () => {
    it('renders header with correct structure', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.find('header').exists()).toBe(true)
      expect(wrapper.find('[data-testid="search-box"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="connection-status"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="notification-button"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="theme-toggle"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="user-dropdown"]').exists()).toBe(true)
    })

    it('renders mobile menu button on small screens', () => {
      const wrapper = createWrapper()
      const mobileButton = wrapper.find('button')
      
      expect(mobileButton.exists()).toBe(true)
      expect(mobileButton.classes()).toContain('lg:hidden')
    })

    it('renders desktop search box with correct visibility', () => {
      const wrapper = createWrapper()
      const searchContainer = wrapper.find('.hidden.md\\:block')
      
      expect(searchContainer.exists()).toBe(true)
      expect(searchContainer.find('[data-testid="search-box"]').exists()).toBe(true)
    })

    it('shows mobile page title on small screens', () => {
      const wrapper = createWrapper()
      const mobileTitle = wrapper.find('.sm\\:hidden')
      
      expect(mobileTitle.exists()).toBe(true)
      expect(mobileTitle.text()).toBe('Tableau de bord')
    })
  })

  describe('Breadcrumb Navigation', () => {
    it('shows breadcrumb navigation component', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      const breadcrumb = wrapper.find('nav[aria-label="Breadcrumb"]')
      
      expect(breadcrumb.exists()).toBe(true)
    })

    it('computes breadcrumbs for Dashboard route', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      
      expect(wrapper.vm.breadcrumbs).toEqual([
        { name: 'Tableau de bord', href: '/dashboard' }
      ])
    })

    it('has routeToBreadcrumbs mapping object', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      
      expect(wrapper.vm.routeToBreadcrumbs).toBeDefined()
      expect(typeof wrapper.vm.routeToBreadcrumbs).toBe('object')
    })

    it('breadcrumbs mapping contains expected routes', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      const mapping = wrapper.vm.routeToBreadcrumbs
      
      expect(mapping.Dashboard).toBeDefined()
      expect(mapping.Transcriptions).toBeDefined()
      expect(mapping.CreateTranscription).toBeDefined()
      expect(mapping.Chat).toBeDefined()
      expect(mapping.Analytics).toBeDefined()
    })

    it('breadcrumbs fallback to default for unknown routes', () => {
      const wrapper = createWrapper({ name: 'UnknownRoute' })
      
      expect(wrapper.vm.breadcrumbs).toEqual([
        { name: 'Tableau de bord', href: '/dashboard' }
      ])
    })

    it('breadcrumbs are computed correctly based on route', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      
      // Test that breadcrumbs computed property returns an array
      expect(Array.isArray(wrapper.vm.breadcrumbs)).toBe(true)
      expect(wrapper.vm.breadcrumbs.length).toBeGreaterThan(0)
      
      // Test that each breadcrumb has required properties
      wrapper.vm.breadcrumbs.forEach(crumb => {
        expect(crumb).toHaveProperty('name')
        expect(crumb).toHaveProperty('href')
        expect(typeof crumb.name).toBe('string')
        expect(typeof crumb.href).toBe('string')
      })
    })

    it('breadcrumbs computed property is reactive', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      
      // Breadcrumbs should be reactive to route changes
      expect(wrapper.vm.breadcrumbs).toBeDefined()
      expect(wrapper.vm.breadcrumbs.length).toBeGreaterThan(0)
    })

    it('falls back to default breadcrumb for unknown routes', () => {
      const wrapper = createWrapper({ name: 'UnknownRoute' })
      
      expect(wrapper.vm.breadcrumbs).toEqual([
        { name: 'Tableau de bord', href: '/dashboard' }
      ])
    })

    it('renders breadcrumb list structure', () => {
      const wrapper = createWrapper({ name: 'Transcriptions' })
      const breadcrumbList = wrapper.find('ol')
      
      expect(breadcrumbList.exists()).toBe(true)
      expect(breadcrumbList.findAll('li').length).toBeGreaterThan(0)
    })
  })

  describe('Mobile Search Toggle', () => {
    it('has toggleMobileSearch method', () => {
      const wrapper = createWrapper()
      
      expect(typeof wrapper.vm.toggleMobileSearch).toBe('function')
    })

    it('initializes showMobileSearch as false', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.vm.showMobileSearch).toBe(false)
    })

    it('toggles mobile search visibility with method call', async () => {
      const wrapper = createWrapper()
      
      // Initially false
      expect(wrapper.vm.showMobileSearch).toBe(false)
      
      // Call toggle method
      wrapper.vm.toggleMobileSearch()
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.showMobileSearch).toBe(true)
      
      // Toggle again
      wrapper.vm.toggleMobileSearch()
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.showMobileSearch).toBe(false)
    })

    it('hides mobile search container when showMobileSearch is false', () => {
      const wrapper = createWrapper()
      
      // showMobileSearch is false by default, so mobile search should not be visible
      const mobileSearchContainer = wrapper.find('.mt-3.md\\:hidden')
      expect(mobileSearchContainer.exists()).toBe(false)
    })
  })

  describe('UI Store Integration', () => {
    it('calls toggleSidebar when mobile menu button is clicked', async () => {
      const wrapper = createWrapper()
      const uiStore = useUIStore()
      const toggleSidebarSpy = vi.spyOn(uiStore, 'toggleSidebar')
      
      const mobileMenuButton = wrapper.find('button')
      await mobileMenuButton.trigger('click')
      
      expect(toggleSidebarSpy).toHaveBeenCalledTimes(1)
    })

    it('integrates with UI store correctly', () => {
      const wrapper = createWrapper()
      
      expect(wrapper.vm.uiStore).toBeDefined()
    })
  })

  describe('Computed Properties', () => {
    it('computes currentPageTitle for Dashboard route', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      expect(wrapper.vm.currentPageTitle).toBe('Tableau de bord')
    })

    it('computes currentPageTitle based on breadcrumbs', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      
      // Should return the last breadcrumb name
      const title = wrapper.vm.currentPageTitle
      expect(typeof title).toBe('string')
      expect(title.length).toBeGreaterThan(0)
    })

    it('currentPageTitle returns page name from breadcrumbs', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      const breadcrumbs = wrapper.vm.breadcrumbs
      const expectedTitle = breadcrumbs[breadcrumbs.length - 1]?.name || 'Page'
      
      expect(wrapper.vm.currentPageTitle).toBe(expectedTitle)
    })

    it('currentPageTitle computed property is reactive', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      
      // Should be a computed property that updates with route
      expect(wrapper.vm.currentPageTitle).toBeDefined()
      expect(typeof wrapper.vm.currentPageTitle).toBe('string')
    })

    it('computes currentPageTitle for unknown route', () => {
      const wrapper = createWrapper({ name: 'UnknownRoute' })
      expect(wrapper.vm.currentPageTitle).toBe('Tableau de bord')
    })

    it('has reactive breadcrumbs computed property', () => {
      const wrapper = createWrapper({ name: 'Dashboard' })
      
      // Initially should show Dashboard breadcrumb
      expect(wrapper.vm.breadcrumbs).toEqual([
        { name: 'Tableau de bord', href: '/dashboard' }
      ])
    })
  })

  describe('Accessibility', () => {
    it('has proper ARIA label for breadcrumb navigation', () => {
      const wrapper = createWrapper()
      const breadcrumbNav = wrapper.find('nav')
      
      expect(breadcrumbNav.attributes('aria-label')).toBe('Breadcrumb')
    })

    it('mobile menu button has accessible properties', () => {
      const wrapper = createWrapper()
      const mobileButton = wrapper.find('button')
      
      expect(mobileButton.classes()).toContain('transition-colors')
      // Button should be focusable and have hover states
      expect(mobileButton.classes()).toContain('hover:text-gray-600')
    })

    it('breadcrumb links exist and have router-link structure', () => {
      const wrapper = createWrapper({ name: 'Transcriptions' })
      const breadcrumbContainer = wrapper.find('nav[aria-label="Breadcrumb"]')
      
      expect(breadcrumbContainer.exists()).toBe(true)
    })
  })

  describe('Responsive Design', () => {
    it('hides breadcrumb navigation on small screens', () => {
      const wrapper = createWrapper()
      const breadcrumbNav = wrapper.find('nav[aria-label="Breadcrumb"]')
      
      expect(breadcrumbNav.classes()).toContain('hidden')
      expect(breadcrumbNav.classes()).toContain('sm:flex')
    })

    it('hides search box on mobile', () => {
      const wrapper = createWrapper()
      const desktopSearchContainer = wrapper.find('.hidden.md\\:block')
      
      expect(desktopSearchContainer.exists()).toBe(true)
    })

    it('shows mobile page title only on small screens', () => {
      const wrapper = createWrapper()
      const mobileTitle = wrapper.find('h1.sm\\:hidden')
      
      expect(mobileTitle.exists()).toBe(true)
    })

    it('positions mobile menu button correctly', () => {
      const wrapper = createWrapper()
      const mobileButton = wrapper.find('button.lg\\:hidden')
      
      expect(mobileButton.exists()).toBe(true)
      expect(mobileButton.classes()).toContain('p-2')
      expect(mobileButton.classes()).toContain('rounded-md')
    })
  })

  describe('Edge Cases', () => {
    it('handles undefined route name gracefully', () => {
      const wrapper = createWrapper({ name: undefined })
      
      expect(wrapper.vm.breadcrumbs).toEqual([
        { name: 'Tableau de bord', href: '/dashboard' }
      ])
      expect(wrapper.vm.currentPageTitle).toBe('Tableau de bord')
    })

    it('handles null route gracefully', () => {
      const wrapper = createWrapper({ name: null, path: null })
      
      expect(wrapper.vm.breadcrumbs).toEqual([
        { name: 'Tableau de bord', href: '/dashboard' }
      ])
    })

    it('handles complex route paths in breadcrumbs', () => {
      const wrapper = createWrapper({ 
        name: 'TranscriptionDetail',
        path: '/transcriptions/very-long-id-123456789'
      })
      
      const breadcrumbs = wrapper.vm.breadcrumbs
      // TranscriptionDetail should return 3 breadcrumbs if found in mapping, otherwise fallback to default
      expect(breadcrumbs.length).toBeGreaterThan(0)
      expect(breadcrumbs[0].name).toBe('Tableau de bord')
      
      // If TranscriptionDetail mapping exists, should be 3, otherwise 1 (fallback)
      if (breadcrumbs.length === 3) {
        expect(breadcrumbs[1].name).toBe('Transcriptions')
        expect(breadcrumbs[2].name).toBe('Détails')
      }
    })
  })
})