import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Input from '../Input.vue'

describe('Input.vue', () => {
  it('renders basic input correctly', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        placeholder: 'Enter text'
      }
    })

    expect(wrapper.find('input').exists()).toBe(true)
    expect(wrapper.find('input').attributes('placeholder')).toBe('Enter text')
  })

  it('handles v-model correctly', async () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: 'initial value',
        'onUpdate:modelValue': (value: string) => wrapper.setProps({ modelValue: value })
      }
    })

    expect(wrapper.find('input').element.value).toBe('initial value')

    await wrapper.find('input').setValue('new value')
    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['new value'])
  })

  it('shows label when provided', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        label: 'Email Address'
      }
    })

    expect(wrapper.find('label').exists()).toBe(true)
    expect(wrapper.find('label').text()).toBe('Email Address')
  })

  it('shows error state and message', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        error: 'This field is required'
      }
    })

    expect(wrapper.find('.border-red-500').exists()).toBe(true)
    expect(wrapper.text()).toContain('This field is required')
  })

  it('shows success state', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: 'valid@email.com',
        success: true
      }
    })

    expect(wrapper.find('.border-green-500').exists()).toBe(true)
  })

  it('handles disabled state', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        disabled: true
      }
    })

    expect(wrapper.find('input').attributes('disabled')).toBeDefined()
    expect(wrapper.find('.opacity-50').exists()).toBe(true)
  })

  it('handles readonly state', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: 'readonly value',
        readonly: true
      }
    })

    expect(wrapper.find('input').attributes('readonly')).toBeDefined()
  })

  it('handles required state', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        required: true,
        label: 'Required Field'
      }
    })

    expect(wrapper.find('input').attributes('required')).toBeDefined()
    expect(wrapper.text()).toContain('*')
  })

  it('handles different input types', () => {
    const types = ['text', 'email', 'password', 'number', 'tel', 'url']
    
    types.forEach(type => {
      const wrapper = mount(Input, {
        props: {
          modelValue: '',
          type: type as any
        }
      })

      expect(wrapper.find('input').attributes('type')).toBe(type)
    })
  })

  it('shows helper text', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        helperText: 'This is a helpful hint'
      }
    })

    expect(wrapper.text()).toContain('This is a helpful hint')
  })

  it('handles prefix and suffix slots', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: ''
      },
      slots: {
        prefix: '<span data-testid="prefix">$</span>',
        suffix: '<span data-testid="suffix">.00</span>'
      }
    })

    expect(wrapper.find('[data-testid="prefix"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="suffix"]').exists()).toBe(true)
  })

  it('handles loading state', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        loading: true
      }
    })

    expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
  })

  it('handles clearable functionality', async () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: 'some text',
        clearable: true,
        'onUpdate:modelValue': (value: string) => wrapper.setProps({ modelValue: value })
      }
    })

    expect(wrapper.find('[data-testid="clear-button"]').exists()).toBe(true)

    await wrapper.find('[data-testid="clear-button"]').trigger('click')
    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([''])
  })

  it('emits focus and blur events', async () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: ''
      }
    })

    await wrapper.find('input').trigger('focus')
    expect(wrapper.emitted('focus')).toBeTruthy()

    await wrapper.find('input').trigger('blur')
    expect(wrapper.emitted('blur')).toBeTruthy()
  })

  it('handles password visibility toggle', async () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: 'password123',
        type: 'password',
        showPasswordToggle: true
      }
    })

    expect(wrapper.find('input').attributes('type')).toBe('password')
    expect(wrapper.find('[data-testid="password-toggle"]').exists()).toBe(true)

    await wrapper.find('[data-testid="password-toggle"]').trigger('click')
    expect(wrapper.find('input').attributes('type')).toBe('text')
  })

  it('applies correct size classes', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        size: 'lg'
      }
    })

    expect(wrapper.find('.px-4').exists()).toBe(true)
    expect(wrapper.find('.py-3').exists()).toBe(true)
  })

  it('handles maxlength attribute', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        maxlength: 100
      }
    })

    expect(wrapper.find('input').attributes('maxlength')).toBe('100')
  })

  it('shows character count when maxlength is set', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: 'hello',
        maxlength: 100,
        showCharacterCount: true
      }
    })

    expect(wrapper.text()).toContain('5/100')
  })

  it('handles autocomplete attribute', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        autocomplete: 'email'
      }
    })

    expect(wrapper.find('input').attributes('autocomplete')).toBe('email')
  })

  it('applies custom classes', () => {
    const wrapper = mount(Input, {
      props: {
        modelValue: '',
        class: 'custom-class'
      }
    })

    expect(wrapper.classes()).toContain('custom-class')
  })
})