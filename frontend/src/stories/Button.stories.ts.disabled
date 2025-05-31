import type { Meta, StoryObj } from '@storybook/vue3';
import Button from '@/components/ui/Button.vue';
import { fn } from '@storybook/test';

const meta: Meta<typeof Button> = {
  title: 'UI/Button',
  component: Button,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['primary', 'secondary', 'ghost', 'danger'],
      description: 'The visual style of the button'
    },
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg'],
      description: 'The size of the button'
    },
    loading: {
      control: 'boolean',
      description: 'Shows loading spinner and disables the button'
    },
    disabled: {
      control: 'boolean',
      description: 'Disables the button'
    },
    fullWidth: {
      control: 'boolean',
      description: 'Makes the button full width'
    },
    type: {
      control: 'select',
      options: ['button', 'submit', 'reset'],
      description: 'The HTML button type'
    }
  },
  args: {
    onClick: fn()
  }
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Primary: Story = {
  args: {
    variant: 'primary'
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Primary Button</Button>'
  })
};

export const Secondary: Story = {
  args: {
    variant: 'secondary'
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Secondary Button</Button>'
  })
};

export const Ghost: Story = {
  args: {
    variant: 'ghost'
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Ghost Button</Button>'
  })
};

export const Danger: Story = {
  args: {
    variant: 'danger'
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Delete</Button>'
  })
};

export const Loading: Story = {
  args: {
    loading: true
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Loading...</Button>'
  })
};

export const Disabled: Story = {
  args: {
    disabled: true
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Disabled</Button>'
  })
};

export const Small: Story = {
  args: {
    size: 'sm'
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Small Button</Button>'
  })
};

export const Large: Story = {
  args: {
    size: 'lg'
  },
  render: (args) => ({
    components: { Button },
    setup() {
      return { args };
    },
    template: '<Button v-bind="args">Large Button</Button>'
  })
};
