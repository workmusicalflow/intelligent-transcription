# Testing Guide

## Vue d'ensemble

Guide complet pour tester l'application Intelligent Transcription.

## 🛠️ Configuration de l'Environnement de Test

### Frontend (Vue.js + Vitest)

```bash
cd frontend
npm install
npm run test
```

### Backend (PHPUnit)

```bash
composer install
vendor/bin/phpunit
```

## 🧪 Types de Tests

### 1. Tests Unitaires

#### Frontend (Vitest + Vue Test Utils)

**Exemple - Test de composant :**
```typescript
// Input.test.ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Input from '@/components/ui/Input.vue'

describe('Input', () => {
  it('renders with placeholder', () => {
    const wrapper = mount(Input, {
      props: {
        placeholder: 'Enter text'
      }
    })
    
    expect(wrapper.find('input').attributes('placeholder')).toBe('Enter text')
  })
  
  it('emits update:modelValue on input', async () => {
    const wrapper = mount(Input)
    const input = wrapper.find('input')
    
    await input.setValue('test value')
    
    expect(wrapper.emitted('update:modelValue')).toEqual([['test value']])
  })
})
```

**Exemple - Test de composable :**
```typescript
// useAuth.test.ts
import { describe, it, expect, vi } from 'vitest'
import { useAuthStore } from '@/stores/auth'
import { createPinia, setActivePinia } from 'pinia'

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })
  
  it('should login user with valid credentials', async () => {
    const authStore = useAuthStore()
    
    // Mock API response
    vi.mock('@/api/auth', () => ({
      authApi: {
        login: vi.fn().mockResolvedValue({
          success: true,
          data: { user: { id: 1, name: 'Test User' }, token: 'jwt-token' }
        })
      }
    }))
    
    await authStore.login('test@example.com', 'password')
    
    expect(authStore.isAuthenticated).toBe(true)
    expect(authStore.user?.name).toBe('Test User')
  })
})
```

#### Backend (PHPUnit)

**Exemple - Test d'entité :**
```php
// TranscriptionTest.php
class TranscriptionTest extends TestCase
{
    public function testCreateTranscription(): void
    {
        $audioFile = new AudioFile('/path/to/audio.mp3', 'audio/mp3', 1024);
        $language = Language::fromCode('fr');
        
        $transcription = Transcription::create(
            TranscriptionId::generate(),
            $audioFile,
            $language
        );
        
        $this->assertEquals(TranscriptionStatus::PENDING, $transcription->status());
        $this->assertEquals('fr', $transcription->language()->code());
    }
    
    public function testStartProcessing(): void
    {
        $transcription = $this->createTranscription();
        
        $transcription->startProcessing();
        
        $this->assertEquals(TranscriptionStatus::PROCESSING, $transcription->status());
    }
}
```

### 2. Tests d'Intégration

#### API Tests
```typescript
// auth.integration.test.ts
describe('Authentication API', () => {
  it('should login with valid credentials', async () => {
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: 'test@example.com',
        password: 'password123'
      })
    })
    
    const data = await response.json()
    
    expect(response.status).toBe(200)
    expect(data.success).toBe(true)
    expect(data.data.token).toBeDefined()
  })
})
```

### 3. Tests End-to-End (Cypress)

**Configuration :**
```typescript
// cypress.config.ts
export default defineConfig({
  e2e: {
    baseUrl: 'http://localhost:5173',
    supportFile: 'cypress/support/e2e.ts'
  }
})
```

**Exemple de test E2E :**
```typescript
// auth.cy.ts
describe('Authentication Flow', () => {
  it('should login and redirect to dashboard', () => {
    cy.visit('/login')
    
    cy.get('[data-testid="email-input"]').type('test@example.com')
    cy.get('[data-testid="password-input"]').type('password123')
    cy.get('[data-testid="login-button"]').click()
    
    cy.url().should('include', '/dashboard')
    cy.get('[data-testid="user-menu"]').should('be.visible')
  })
  
  it('should create transcription', () => {
    cy.login() // Custom command
    
    cy.visit('/transcriptions/create')
    cy.get('[data-testid="file-input"]').attachFile('test-audio.mp3')
    cy.get('[data-testid="submit-button"]').click()
    
    cy.get('[data-testid="success-message"]').should('be.visible')
  })
})
```

## 📋 Conventions de Test

### Naming
- **Fichiers** : `ComponentName.test.ts` ou `ComponentName.spec.ts`
- **Tests** : Description claire de ce qui est testé
- **Data attributes** : `data-testid="element-name"`

### Structure
```typescript
describe('ComponentName', () => {
  beforeEach(() => {
    // Setup
  })
  
  describe('when condition', () => {
    it('should do something', () => {
      // Arrange
      // Act  
      // Assert
    })
  })
})
```

## 📈 Couverture de Code

### Objectifs
- **Couverture globale** : >80%
- **Composants critiques** : >90%
- **Logique métier** : 100%

### Génération des rapports
```bash
# Frontend
npm run test:coverage

# Backend  
vendor/bin/phpunit --coverage-html coverage/
```

## 🚀 CI/CD et Tests

### GitHub Actions
```yaml
- name: Run Frontend Tests
  run: |
    cd frontend
    npm ci
    npm run test:coverage
    
- name: Run Backend Tests
  run: |
    composer install
    vendor/bin/phpunit --coverage-text
```

## 🔧 Outils et Utilitaires

### Mocks et Stubs
```typescript
// API Mock
vi.mock('@/api/transcriptions', () => ({
  transcriptionApi: {
    create: vi.fn().mockResolvedValue({ id: 'tr_123' }),
    list: vi.fn().mockResolvedValue({ transcriptions: [] })
  }
}))

// Router Mock
const mockRouter = {
  push: vi.fn(),
  replace: vi.fn()
}
```

### Test Helpers
```typescript
// test-utils.ts
export function createWrapper(component: Component, props = {}) {
  return mount(component, {
    props,
    global: {
      plugins: [createTestingPinia()]
    }
  })
}

export function waitForNextTick() {
  return new Promise(resolve => setTimeout(resolve, 0))
}
```

## 🚫 Tests à Éviter

- **Tests de détails d'implémentation** : Tester le comportement, pas l'implémentation
- **Tests trop spécifiques** : Qui cassent pour des changements mineurs
- **Tests sans assertions** : Toujours vérifier un résultat

## 📅 Commandes Utiles

```bash
# Lancer tous les tests
npm run test

# Mode watch
npm run test:watch

# Tests d'un fichier spécifique
npm run test Input.test.ts

# Tests E2E
npm run cypress:run
npm run cypress:open

# Backend tests
vendor/bin/phpunit
vendor/bin/phpunit --filter TranscriptionTest
```