import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Button from './Button.vue'

describe('Button Component', () => {
  it('renders slot content', () => {
    const wrapper = mount(Button, {
      slots: {
        default: 'Click me'
      }
    })
    expect(wrapper.text()).toContain('Click me')
  })

  it('emits click event when clicked', async () => {
    const wrapper = mount(Button)
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
    expect(wrapper.emitted('click')!.length).toBe(1)
  })

  it('does not emit click when disabled', async () => {
    const wrapper = mount(Button, {
      props: { disabled: true }
    })
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeFalsy()
  })

  it('does not emit click when loading', async () => {
    const wrapper = mount(Button, {
      props: { loading: true }
    })
    await wrapper.trigger('click')
    expect(wrapper.emitted('click')).toBeFalsy()
  })

  it('applies correct variant classes', () => {
    const variants = ['primary', 'secondary', 'ghost', 'danger'] as const
    
    variants.forEach(variant => {
      const wrapper = mount(Button, {
        props: { variant }
      })
      expect(wrapper.classes()).toContain(`button-${variant}`)
    })
  })

  it('applies correct size classes', () => {
    const sizes = ['sm', 'md', 'lg'] as const
    
    sizes.forEach(size => {
      const wrapper = mount(Button, {
        props: { size }
      })
      expect(wrapper.classes()).toContain(`button-${size}`)
    })
  })

  it('shows loading spinner when loading', () => {
    const wrapper = mount(Button, {
      props: { loading: true }
    })
    expect(wrapper.findComponent({ name: 'LoadingSpinner' }).exists()).toBe(true)
  })

  it('applies fullWidth class when fullWidth prop is true', () => {
    const wrapper = mount(Button, {
      props: { fullWidth: true }
    })
    expect(wrapper.classes()).toContain('w-full')
  })

  it('renders as different HTML element based on type', () => {
    const wrapper = mount(Button, {
      props: { type: 'submit' }
    })
    expect(wrapper.element.getAttribute('type')).toBe('submit')
  })

  it('can render as a link when "to" prop is provided', () => {
    const wrapper = mount(Button, {
      props: { to: '/dashboard' },
      global: {
        stubs: {
          RouterLink: true
        }
      }
    })
    expect(wrapper.findComponent({ name: 'RouterLink' }).exists()).toBe(true)
  })

  it('handles keyboard events correctly', async () => {
    const onClick = vi.fn()
    const wrapper = mount(Button, {
      attrs: {
        onClick
      }
    })

    await wrapper.trigger('keydown.enter')
    expect(onClick).toHaveBeenCalledTimes(1)

    await wrapper.trigger('keydown.space')
    expect(onClick).toHaveBeenCalledTimes(2)
  })

  it('applies custom classes', () => {
    const wrapper = mount(Button, {
      props: {
        class: 'custom-class'
      }
    })
    expect(wrapper.classes()).toContain('custom-class')
  })

  it('has correct accessibility attributes', () => {
    const wrapper = mount(Button, {
      props: {
        disabled: true,
        loading: true
      }
    })
    
    expect(wrapper.attributes('disabled')).toBeDefined()
    expect(wrapper.attributes('aria-busy')).toBe('true')
    expect(wrapper.attributes('aria-disabled')).toBe('true')
  })
})