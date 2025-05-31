# 🚀 Guide de Démarrage des Serveurs

## Prérequis
- PHP 8.3+ ✅
- Node.js 18+ 
- MySQL/SQLite pour la base de données
- Python 3.8+ avec les dépendances (pour les services de transcription)

## 📦 Installation des dépendances

### 1. Backend PHP
```bash
cd /Users/ns2poportable/Desktop/inteligent-transcription
composer install
```

### 2. Frontend Vue 3
```bash
cd frontend
npm install
```

### 3. Python (pour les services IA)
```bash
cd /Users/ns2poportable/Desktop/inteligent-transcription
pip install -r requirements.txt
```

## 🖥️ Lancement des serveurs

### Terminal 1 : Backend PHP (API)
```bash
cd /Users/ns2poportable/Desktop/inteligent-transcription
php -S localhost:8000
```

### Terminal 2 : Frontend Vue 3
```bash
cd /Users/ns2poportable/Desktop/inteligent-transcription/frontend
npm run dev
```

### Terminal 3 : Worker Asynchrone PHP (optionnel)
```bash
cd /Users/ns2poportable/Desktop/inteligent-transcription
php worker.php
```

## 🌐 URLs d'accès

- **Frontend Vue 3** : http://localhost:5173
- **Backend PHP API** : http://localhost:8000
- **GraphQL Playground** : http://localhost:8000/graphql
- **Storybook** : http://localhost:6006 (npm run storybook)

## 🧪 Tests

### Frontend
```bash
cd frontend
npm run test:unit    # Tests unitaires avec Vitest
npm run test:e2e     # Tests E2E avec Cypress
npm run type-check   # Vérification TypeScript
```

### Backend
```bash
cd /Users/ns2poportable/Desktop/inteligent-transcription
./vendor/bin/phpunit
```

## 📱 Fonctionnalités à tester

1. **Authentification**
   - Login/Logout
   - Inscription
   - Gestion du profil

2. **Transcription**
   - Upload de fichier audio
   - Transcription YouTube
   - Visualisation des résultats
   - Export (JSON, TXT, SRT)

3. **Chat Contextuel**
   - Conversation basée sur la transcription
   - Historique des conversations

4. **Analytics**
   - Dashboard des statistiques
   - Graphiques d'utilisation

5. **PWA**
   - Installation de l'app
   - Mode hors ligne
   - Notifications push

## 🐛 Débogage

Si vous rencontrez des problèmes :

1. Vérifiez les logs PHP : `tail -f php_errors.log`
2. Console du navigateur pour les erreurs JS
3. Network tab pour les requêtes API
4. Vérifiez que tous les services sont lancés

## 🔧 Configuration

Assurez-vous que les fichiers de configuration sont corrects :
- `.env` pour les variables d'environnement
- `config.php` pour la configuration PHP
- `frontend/.env` pour le frontend Vue