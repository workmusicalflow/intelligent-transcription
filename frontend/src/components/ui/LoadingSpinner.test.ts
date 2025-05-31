import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import LoadingSpinner from './LoadingSpinner.vue'

describe('LoadingSpinner Component', () => {
  it('renders spinner element', () => {
    const wrapper = mount(LoadingSpinner)
    expect(wrapper.find('svg').exists()).toBe(true)
  })

  it('applies correct size classes', () => {
    const sizes = ['sm', 'md', 'lg'] as const
    
    sizes.forEach(size => {
      const wrapper = mount(LoadingSpinner, {
        props: { size }
      })
      
      const expectedSizes = {
        sm: 'h-4 w-4',
        md: 'h-6 w-6',
        lg: 'h-8 w-8'
      }
      
      const svg = wrapper.find('svg')
      expect(svg.classes()).toContain(expectedSizes[size].split(' ')[0])
      expect(svg.classes()).toContain(expectedSizes[size].split(' ')[1])
    })
  })

  it('applies default size when no size prop provided', () => {
    const wrapper = mount(LoadingSpinner)
    const svg = wrapper.find('svg')
    
    expect(svg.classes()).toContain('h-6')
    expect(svg.classes()).toContain('w-6')
  })

  it('applies custom classes', () => {
    const wrapper = mount(LoadingSpinner, {
      props: {
        class: 'custom-spinner-class'
      }
    })
    
    expect(wrapper.find('svg').classes()).toContain('custom-spinner-class')
  })

  it('has spinning animation class', () => {
    const wrapper = mount(LoadingSpinner)
    expect(wrapper.find('svg').classes()).toContain('animate-spin')
  })

  it('applies correct color classes', () => {
    const wrapper = mount(LoadingSpinner)
    const svg = wrapper.find('svg')
    
    expect(svg.classes()).toContain('text-primary-600')
    expect(svg.classes()).toContain('dark:text-primary-400')
  })

  it('renders accessible SVG with proper attributes', () => {
    const wrapper = mount(LoadingSpinner)
    const svg = wrapper.find('svg')
    
    expect(svg.attributes('role')).toBe('status')
    expect(svg.attributes('aria-hidden')).toBe('true')
    expect(svg.attributes('viewBox')).toBe('0 0 24 24')
    expect(svg.attributes('fill')).toBe('none')
  })

  it('contains correct SVG path elements', () => {
    const wrapper = mount(LoadingSpinner)
    const circles = wrapper.findAll('circle')
    
    expect(circles).toHaveLength(2)
    
    // Background circle
    expect(circles[0].attributes('cx')).toBe('12')
    expect(circles[0].attributes('cy')).toBe('12')
    expect(circles[0].attributes('r')).toBe('10')
    expect(circles[0].attributes('stroke')).toBe('currentColor')
    expect(circles[0].classes()).toContain('opacity-25')
    
    // Animated circle
    expect(circles[1].attributes('cx')).toBe('12')
    expect(circles[1].attributes('cy')).toBe('12')
    expect(circles[1].attributes('r')).toBe('10')
    expect(circles[1].attributes('stroke')).toBe('currentColor')
    expect(circles[1].attributes('stroke-dasharray')).toBeDefined()
  })

  it('component name is correct', () => {
    const wrapper = mount(LoadingSpinner)
    expect(wrapper.vm.$options.name).toBe('LoadingSpinner')
  })
})