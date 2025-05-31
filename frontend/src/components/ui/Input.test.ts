import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Input from './Input.vue'

describe('Input Component', () => {
  it('renders input with correct type', () => {
    const wrapper = mount(Input, {
      props: {
        type: 'email',
        modelValue: 'test@example.com'
      }
    })
    const input = wrapper.find('input')
    expect(input.attributes('type')).toBe('email')
    expect(input.element.value).toBe('test@example.com')
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: ''
      }
    })
    
    const input = wrapper.find('input')
    await input.setValue('new value')
    
    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')![0]).toEqual(['new value'])
  })

  it('displays label when provided', () => {
    const wrapper = mount(Input, {
      props: {
        label: 'Email Address',
        id: 'email'
      }
    })
    
    const label = wrapper.find('label')
    expect(label.exists()).toBe(true)
    expect(label.text()).toBe('Email Address')
    expect(label.attributes('for')).toBe('email')
  })

  it('shows error message and applies error styles', () => {
    const wrapper = mount(Input, {
      props: {
        error: 'Email is required'
      }
    })
    
    expect(wrapper.text()).toContain('Email is required')
    expect(wrapper.find('input').classes()).toContain('border-red-500')
  })

  it('shows helper text when provided', () => {
    const wrapper = mount(Input, {
      props: {
        helperText: 'Enter your email address'
      }
    })
    
    expect(wrapper.text()).toContain('Enter your email address')
  })

  it('disables input when disabled prop is true', () => {
    const wrapper = mount(Input, {
      props: {
        disabled: true
      }
    })
    
    expect(wrapper.find('input').attributes('disabled')).toBeDefined()
  })

  it('marks input as required when required prop is true', () => {
    const wrapper = mount(Input, {
      props: {
        required: true
      }
    })
    
    expect(wrapper.find('input').attributes('required')).toBeDefined()
  })

  it('applies placeholder text', () => {
    const wrapper = mount(Input, {
      props: {
        placeholder: 'Enter text here'
      }
    })
    
    expect(wrapper.find('input').attributes('placeholder')).toBe('Enter text here')
  })

  it('renders prefix content', () => {
    const wrapper = mount(Input, {
      slots: {
        prefix: '<span>$</span>'
      }
    })
    
    expect(wrapper.html()).toContain('<span>$</span>')
  })

  it('renders suffix content', () => {
    const wrapper = mount(Input, {
      slots: {
        suffix: '<span>.com</span>'
      }
    })
    
    expect(wrapper.html()).toContain('<span>.com</span>')
  })

  it('handles focus and blur events', async () => {
    const onFocus = vi.fn()
    const onBlur = vi.fn()
    
    const wrapper = mount(Input, {
      attrs: {
        onFocus,
        onBlur
      }
    })
    
    const input = wrapper.find('input')
    await input.trigger('focus')
    expect(onFocus).toHaveBeenCalledTimes(1)
    
    await input.trigger('blur')
    expect(onBlur).toHaveBeenCalledTimes(1)
  })

  it('validates input on blur when validate prop is provided', async () => {
    const validate = vi.fn().mockReturnValue('Invalid email')
    
    const wrapper = mount(Input, {
      props: {
        modelValue: 'invalid',
        validate
      }
    })
    
    const input = wrapper.find('input')
    await input.trigger('blur')
    
    expect(validate).toHaveBeenCalledWith('invalid')
    expect(wrapper.emitted('error')).toBeTruthy()
    expect(wrapper.emitted('error')![0]).toEqual(['Invalid email'])
  })

  it('clears error on valid input', async () => {
    const validate = vi.fn()
      .mockReturnValueOnce('Error')
      .mockReturnValueOnce(undefined)
    
    const wrapper = mount(Input, {
      props: {
        modelValue: 'test',
        validate
      }
    })
    
    const input = wrapper.find('input')
    
    // First validation - error
    await input.trigger('blur')
    expect(wrapper.emitted('error')![0]).toEqual(['Error'])
    
    // Update value and validate again - no error
    await input.setValue('valid')
    await input.trigger('blur')
    expect(wrapper.emitted('error')![1]).toEqual([undefined])
  })

  it('applies custom classes', () => {
    const wrapper = mount(Input, {
      props: {
        class: 'custom-input-class'
      }
    })
    
    expect(wrapper.classes()).toContain('custom-input-class')
  })

  it('supports different input sizes', () => {
    const sizes = ['sm', 'md', 'lg'] as const
    
    sizes.forEach(size => {
      const wrapper = mount(Input, {
        props: { size }
      })
      expect(wrapper.find('input').classes()).toContain(`input-${size}`)
    })
  })

  it('handles maxlength attribute', () => {
    const wrapper = mount(Input, {
      props: {
        maxlength: 10
      }
    })
    
    expect(wrapper.find('input').attributes('maxlength')).toBe('10')
  })

  it('supports autocomplete attribute', () => {
    const wrapper = mount(Input, {
      props: {
        autocomplete: 'email'
      }
    })
    
    expect(wrapper.find('input').attributes('autocomplete')).toBe('email')
  })
})