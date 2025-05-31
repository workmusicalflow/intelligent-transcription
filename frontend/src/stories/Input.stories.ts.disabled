import type { Meta, StoryObj } from '@storybook/vue3';
import Input from '@/components/ui/Input.vue';
import { fn } from '@storybook/test';
import { EnvelopeIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

const meta: Meta<typeof Input> = {
  title: 'UI/Input',
  component: Input,
  tags: ['autodocs'],
  argTypes: {
    type: {
      control: 'select',
      options: ['text', 'email', 'password', 'number', 'tel', 'url'],
      description: 'The input type'
    },
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg'],
      description: 'The size of the input'
    },
    label: {
      control: 'text',
      description: 'Label text for the input'
    },
    placeholder: {
      control: 'text',
      description: 'Placeholder text'
    },
    helperText: {
      control: 'text',
      description: 'Helper text shown below the input'
    },
    error: {
      control: 'text',
      description: 'Error message'
    },
    disabled: {
      control: 'boolean',
      description: 'Disables the input'
    },
    required: {
      control: 'boolean',
      description: 'Makes the input required'
    },
    modelValue: {
      control: 'text',
      description: 'The v-model value'
    }
  },
  args: {
    'onUpdate:modelValue': fn(),
    onFocus: fn(),
    onBlur: fn()
  }
};

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    placeholder: 'Enter text...'
  }
};

export const WithLabel: Story = {
  args: {
    label: 'Email Address',
    placeholder: 'john@example.com',
    type: 'email'
  }
};

export const WithHelperText: Story = {
  args: {
    label: 'Password',
    type: 'password',
    helperText: 'Must be at least 8 characters'
  }
};

export const WithError: Story = {
  args: {
    label: 'Email',
    type: 'email',
    modelValue: 'invalid-email',
    error: 'Please enter a valid email address'
  }
};

export const Disabled: Story = {
  args: {
    label: 'Disabled Input',
    placeholder: 'Cannot edit this',
    disabled: true,
    modelValue: 'Disabled value'
  }
};

export const Required: Story = {
  args: {
    label: 'Required Field',
    placeholder: 'This field is required',
    required: true
  }
};

export const Small: Story = {
  args: {
    size: 'sm',
    placeholder: 'Small input'
  }
};

export const Large: Story = {
  args: {
    size: 'lg',
    placeholder: 'Large input'
  }
};

export const WithIcon: Story = {
  render: (args) => ({
    components: { Input, EnvelopeIcon },
    setup() {
      return { args };
    },
    template: `
      <Input v-bind="args">
        <template #prefix>
          <EnvelopeIcon class="h-5 w-5 text-gray-400" />
        </template>
      </Input>
    `
  }),
  args: {
    type: 'email',
    placeholder: 'Enter your email'
  }
};

export const WithSuffix: Story = {
  render: (args) => ({
    components: { Input },
    setup() {
      return { args };
    },
    template: `
      <Input v-bind="args">
        <template #suffix>
          <span class="text-gray-500">.com</span>
        </template>
      </Input>
    `
  }),
  args: {
    placeholder: 'yourwebsite'
  }
};

export const PasswordWithIcon: Story = {
  render: (args) => ({
    components: { Input, LockClosedIcon },
    setup() {
      return { args };
    },
    template: `
      <Input v-bind="args">
        <template #prefix>
          <LockClosedIcon class="h-5 w-5 text-gray-400" />
        </template>
      </Input>
    `
  }),
  args: {
    type: 'password',
    label: 'Password',
    placeholder: 'Enter your password'
  }
};

export const FormExample: Story = {
  render: () => ({
    components: { Input },
    template: `
      <form class="space-y-4 max-w-md">
        <Input
          label="Full Name"
          placeholder="John Doe"
          required
        />
        <Input
          label="Email"
          type="email"
          placeholder="john@example.com"
          required
        />
        <Input
          label="Password"
          type="password"
          placeholder="••••••••"
          helperText="Minimum 8 characters"
          required
        />
        <Input
          label="Phone"
          type="tel"
          placeholder="+1 (555) 000-0000"
        />
      </form>
    `
  })
};

export const AllStates: Story = {
  render: () => ({
    components: { Input },
    template: `
      <div class="space-y-4 max-w-md">
        <Input
          label="Normal"
          placeholder="Normal input"
        />
        <Input
          label="With Value"
          modelValue="This has a value"
        />
        <Input
          label="Focused"
          placeholder="This appears focused"
          class="ring-2 ring-primary-500"
        />
        <Input
          label="Disabled"
          placeholder="Disabled input"
          disabled
        />
        <Input
          label="With Error"
          modelValue="Invalid input"
          error="This field has an error"
        />
      </div>
    `
  })
};