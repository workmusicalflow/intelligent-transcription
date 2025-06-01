# Environment Setup Guide

## Vue d'ensemble

Guide complet pour configurer votre environnement de développement.

## 💻 Prérequis Système

### Logiciels Requis

#### Backend
- **PHP 8.2+** avec extensions :
  - `pdo_sqlite`
  - `fileinfo`
  - `curl`
  - `openssl`
  - `mbstring`
- **Composer 2.0+**
- **SQLite 3**

#### Frontend
- **Node.js 18+**
- **npm 8+** ou **yarn 1.22+**

#### Optionnel
- **Python 3.9+** (pour scripts d'analyse)
- **Docker** (pour déploiement)

### Vérification Installation
```bash
# PHP
php --version
php -m | grep -E "(pdo_sqlite|fileinfo|curl|openssl)"

# Composer
composer --version

# Node.js
node --version
npm --version

# SQLite
sqlite3 --version
```

## 🚀 Installation Développement

### 1. Clone du Projet
```bash
git clone https://github.com/workmusicalflow/intelligent-transcription.git
cd intelligent-transcription
```

### 2. Configuration Backend

#### Dépendances PHP
```bash
composer install
```

#### Configuration
```bash
# Copier le fichier de configuration
cp config.example.php config.php

# Éditer avec vos clés API
nano config.php
```

**Contenu config.php :**
```php
<?php
return [
    'openai' => [
        'api_key' => 'sk-...', // Votre clé OpenAI
        'organization' => '',  // Optionnel
    ],
    'video_download' => [
        'api_key' => '',  // Clé pour téléchargement YouTube
    ],
    'database' => [
        'path' => __DIR__ . '/database.sqlite'
    ],
    'security' => [
        'jwt_secret' => 'your-secret-key-here',
        'cors_origin' => 'http://localhost:5173'
    ],
    'app' => [
        'env' => 'development',
        'debug' => true,
        'url' => 'http://localhost:8000'
    ]
];
```

#### Initialisation Base de Données
```bash
# Créer les tables
php migrate.php

# Créer un utilisateur admin
php install_auth.php
# Username: admin
# Password: admin123
```

### 3. Configuration Frontend

#### Dépendances Node.js
```bash
cd frontend
npm install
```

#### Variables d'environnement
```bash
# Copier le fichier d'environnement
cp .env.example .env.local

# Éditer les variables
nano .env.local
```

**Contenu .env.local :**
```env
# API Backend
VITE_API_URL=http://localhost:8000
VITE_GRAPHQL_URL=http://localhost:8000/graphql
VITE_WS_URL=ws://localhost:8000/ws

# Sentry (optionnel)
VITE_SENTRY_DSN=

# Environnement
VITE_APP_ENV=development
```

## 🏠 Démarrage des Serveurs

### Méthode Automatique
```bash
# Depuis la racine du projet
./start-servers.sh
```

### Méthode Manuelle

#### Backend (Terminal 1)
```bash
php -S localhost:8000 -c php.ini router.php
```

#### Frontend (Terminal 2)
```bash
cd frontend
npm run dev
```

### Accès aux Applications
- **Frontend** : http://localhost:5173
- **Backend API** : http://localhost:8000/api
- **GraphQL Playground** : http://localhost:8000/graphql

## 🔧 Outils de Développement

### Éditeur de Code (Recommandé : VS Code)

#### Extensions PHP
```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "xdebug.php-debug",
    "recca0120.vscode-phpunit",
    "junstyle.php-cs-fixer"
  ]
}
```

#### Extensions Vue/TypeScript
```json
{
  "recommendations": [
    "Vue.volar",
    "Vue.vscode-typescript-vue-plugin",
    "bradlc.vscode-tailwindcss",
    "esbenp.prettier-vscode",
    "dbaeumer.vscode-eslint"
  ]
}
```

### Configuration Workspace
```json
// .vscode/settings.json
{
  "php.validate.executablePath": "/usr/bin/php",
  "intelephense.files.maxSize": 3000000,
  "editor.formatOnSave": true,
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": true
  },
  "tailwindCSS.includeLanguages": {
    "vue": "html"
  }
}
```

## 🧪 Configuration Tests

### Backend Tests
```bash
# Lancer tous les tests
vendor/bin/phpunit

# Tests avec couverture
vendor/bin/phpunit --coverage-html coverage/

# Tests spécifiques
vendor/bin/phpunit --filter TranscriptionTest
```

### Frontend Tests
```bash
cd frontend

# Tests unitaires
npm run test

# Tests avec interface
npm run test:ui

# Couverture
npm run test:coverage

# Tests E2E
npm run cypress:open
```

## 🐛 Débogage

### Backend (Xdebug)

#### Configuration php.ini
```ini
[xdebug]
zend_extension=xdebug
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=localhost
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
```

#### VS Code launch.json
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/path/to/project": "${workspaceFolder}"
      }
    }
  ]
}
```

### Frontend (Vue DevTools)
```bash
# Installer l'extension navigateur Vue DevTools
# https://devtools.vuejs.org/

# Debug mode dans .env.local
VITE_APP_DEBUG=true
```

## 📊 Performance

### Monitoring Local
```bash
# Backend - Profiling avec Xdebug
echo "xdebug.mode=profile" >> php.ini

# Frontend - Build analysis
cd frontend
npm run build -- --analyze

# Lighthouse audit
npx lighthouse http://localhost:5173
```

### Optimisations Développement
```bash
# Cache Composer
composer dump-autoload --optimize

# Cache npm
npm ci --cache .npm

# Précompilation Vue
cd frontend
npm run build-dev
```

## 🔐 Sécurité Locale

### Permissions Fichiers
```bash
# Droits recommandés
chmod 755 .
chmod 644 config.php
chmod 755 uploads/
chmod 666 database.sqlite
```

### Variables Sensibles
```bash
# Ne jamais committer
echo "config.php" >> .gitignore
echo "frontend/.env.local" >> .gitignore
echo "database.sqlite" >> .gitignore
```

## 🌍 Variables d'Environnement

### Développement
```bash
export OPENAI_API_KEY="sk-..."
export JWT_SECRET="dev-secret-key"
export APP_ENV="development"
export APP_DEBUG="true"
```

### Production
```bash
export OPENAI_API_KEY="sk-..."
export JWT_SECRET="secure-production-key"
export APP_ENV="production"
export APP_DEBUG="false"
export CORS_ORIGIN="https://yourdomain.com"
```

## 🚫 Problèmes Courants

### "Class not found"
```bash
# Regenerer l'autoloader
composer dump-autoload
```

### "Permission denied" (uploads)
```bash
# Corriger les permissions
sudo chmod 755 uploads/
sudo chown $USER:$USER uploads/
```

### "Module not found" (npm)
```bash
# Nettoyer et réinstaller
cd frontend
rm -rf node_modules package-lock.json
npm install
```

### "Database locked"
```bash
# Vérifier les processus
lsof database.sqlite
# Tuer si nécessaire
kill PID
```

### Port déjà utilisé
```bash
# Trouver le processus
lsof -i :8000
lsof -i :5173

# Changer le port
php -S localhost:8001 router.php
# ou dans frontend:
npm run dev -- --port 5174
```

## 📚 Ressources Supplémentaires

### Documentation
- [PHP Manual](https://www.php.net/manual/)
- [Vue.js Guide](https://vuejs.org/guide/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Tailwind CSS Docs](https://tailwindcss.com/docs)

### Outils Utiles
- [Postman/Insomnia](https://www.postman.com/) - Test API
- [TablePlus/DB Browser](https://tableplus.com/) - SQLite GUI
- [Vue DevTools](https://devtools.vuejs.org/) - Debug Vue
- [Xdebug](https://xdebug.org/) - Debug PHP

---

**Votre environnement est maintenant prêt ! 🎉**