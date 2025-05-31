# Frontend Architecture

## Overview
The frontend is built with Vue.js 3 using the Composition API and TypeScript for type safety.

## Project Structure

```
src/
├── api/           # API client and authentication
├── components/    # Reusable Vue components
│   ├── ui/        # Generic UI components
│   ├── layout/    # Layout components
│   └── ...        # Feature-specific components
├── composables/   # Vue composables for reusable logic
├── stores/        # Pinia state management
├── types/         # TypeScript type definitions
├── views/         # Page components
└── router/        # Vue Router configuration
```

## State Management

### Stores
- **AuthStore:** User authentication and session management
- **UIStore:** Global UI state (notifications, modals, theme)
- **AppStore:** Application-wide state and settings

### Data Flow
1. Components dispatch actions to stores
2. Stores update reactive state
3. Components reactively update based on state changes
4. API calls handled through dedicated API modules

## Component Architecture

### Component Categories
1. **UI Components** (`components/ui/`)
   - Generic, reusable components
   - No business logic
   - Props-based configuration

2. **Layout Components** (`components/layout/`)
   - App structure components
   - Navigation, headers, sidebars

3. **Feature Components** (`components/[feature]/`)
   - Business logic components
   - Feature-specific functionality

4. **Page Components** (`views/`)
   - Route-level components
   - Compose multiple components

### Composition API Patterns
- Use `<script setup>` syntax for cleaner code
- Extract reusable logic into composables
- Prefer computed properties over methods for derived state
- Use reactive() for complex object state, ref() for primitives

