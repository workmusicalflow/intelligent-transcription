import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Button from '../Button.vue'

describe('Button.vue', () => {
  it('renders default button correctly', () => {
    const wrapper = mount(Button, {
      slots: {
        default: 'Click me'
      }
    })

    expect(wrapper.text()).toBe('Click me')
    expect(wrapper.find('button').exists()).toBe(true)
  })

  it('applies correct variant classes', () => {
    const wrapper = mount(Button, {
      props: {
        variant: 'primary'
      },
      slots: {
        default: 'Primary Button'
      }
    })

    expect(wrapper.classes()).toContain('bg-blue-500')
  })

  it('applies correct size classes', () => {
    const wrapper = mount(Button, {
      props: {
        size: 'lg'
      },
      slots: {
        default: 'Large Button'
      }
    })

    expect(wrapper.classes()).toContain('px-6')
    expect(wrapper.classes()).toContain('py-3')
  })

  it('handles disabled state', () => {
    const wrapper = mount(Button, {
      props: {
        disabled: true
      },
      slots: {
        default: 'Disabled Button'
      }
    })

    expect(wrapper.attributes('disabled')).toBeDefined()
    expect(wrapper.classes()).toContain('opacity-50')
  })

  it('shows loading state', () => {
    const wrapper = mount(Button, {
      props: {
        loading: true
      },
      slots: {
        default: 'Loading Button'
      }
    })

    expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
    expect(wrapper.attributes('disabled')).toBeDefined()
  })

  it('emits click event', async () => {
    const wrapper = mount(Button, {
      slots: {
        default: 'Click me'
      }
    })

    await wrapper.trigger('click')
    expect(wrapper.emitted().click).toBeTruthy()
  })

  it('does not emit click when disabled', async () => {
    const wrapper = mount(Button, {
      props: {
        disabled: true
      },
      slots: {
        default: 'Disabled Button'
      }
    })

    await wrapper.trigger('click')
    expect(wrapper.emitted().click).toBeFalsy()
  })

  it('does not emit click when loading', async () => {
    const wrapper = mount(Button, {
      props: {
        loading: true
      },
      slots: {
        default: 'Loading Button'
      }
    })

    await wrapper.trigger('click')
    expect(wrapper.emitted().click).toBeFalsy()
  })

  it('renders as different HTML elements when using "as" prop', () => {
    const wrapper = mount(Button, {
      props: {
        as: 'a',
        href: 'https://example.com'
      },
      slots: {
        default: 'Link Button'
      }
    })

    expect(wrapper.find('a').exists()).toBe(true)
    expect(wrapper.attributes('href')).toBe('https://example.com')
  })

  it('applies full width when specified', () => {
    const wrapper = mount(Button, {
      props: {
        fullWidth: true
      },
      slots: {
        default: 'Full Width Button'
      }
    })

    expect(wrapper.classes()).toContain('w-full')
  })

  it('renders icon correctly', () => {
    const wrapper = mount(Button, {
      props: {
        icon: 'plus'
      },
      slots: {
        default: 'Button with Icon'
      }
    })

    expect(wrapper.find('[data-testid="button-icon"]').exists()).toBe(true)
  })

  it('handles all variant types', () => {
    const variants = ['primary', 'secondary', 'danger', 'ghost', 'outline']
    
    variants.forEach(variant => {
      const wrapper = mount(Button, {
        props: {
          variant: variant as any
        },
        slots: {
          default: `${variant} Button`
        }
      })

      expect(wrapper.exists()).toBe(true)
    })
  })

  it('handles all size types', () => {
    const sizes = ['sm', 'md', 'lg', 'xl']
    
    sizes.forEach(size => {
      const wrapper = mount(Button, {
        props: {
          size: size as any
        },
        slots: {
          default: `${size} Button`
        }
      })

      expect(wrapper.exists()).toBe(true)
    })
  })
})