# Deployment Guide

## Vue d'ensemble

Guide complet pour déployer Intelligent Transcription en production.

## 🏠 Environnements

### Développement
```bash
# Démarrage rapide
git clone https://github.com/workmusicalflow/intelligent-transcription.git
cd intelligent-transcription

# Backend
composer install
cp config.example.php config.php
# Configurer les clés API

# Frontend
cd frontend
npm install

# Démarrer les serveurs
./start-servers.sh
```

### Production

## 🚀 Déploiement cPanel (Recommandé)

### 1. Préparation

#### Structure cPanel
```
public_html/
├── index.html          # Vue.js SPA
├── assets/             # JS/CSS compilés
├── api/                # Points d'entrée API
└── app/                # Backend PHP (hors web root)
    ├── src/            # Code source
    ├── vendor/         # Dépendances Composer
    ├── database.sqlite # Base de données
    └── uploads/        # Fichiers utilisateur
```

#### Configuration PHP
```ini
; php.ini (dans public_html)
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 100M
post_max_size = 100M
max_file_uploads = 20

; Extensions requises
extension=pdo_sqlite
extension=fileinfo
extension=curl
extension=openssl
```

### 2. Build et Upload

#### Script de build automatique
```bash
#!/bin/bash
# deploy.sh

echo "🛠️ Building for production..."

# Build frontend
cd frontend
npm ci
npm run build
cd ..

# Préparation des fichiers
mkdir -p deploy/public_html
mkdir -p deploy/app

# Copier frontend build
cp -r frontend/dist/* deploy/public_html/

# Copier backend
cp -r src/ deploy/app/
cp -r vendor/ deploy/app/
cp config.php deploy/app/
cp router.php deploy/public_html/

# Créer les API endpoints
mkdir -p deploy/public_html/api
cp -r api/* deploy/public_html/api/

echo "✅ Build terminé dans deploy/"
```

#### Upload via cPanel File Manager
1. **Zipper le contenu** de `deploy/`
2. **Uploader** le zip dans cPanel
3. **Extraire** dans le bon répertoire
4. **Configurer** les permissions

### 3. Configuration Base de Données

```bash
# Dans cPanel Terminal ou SSH
cd app/
php migrate.php
php install_auth.php
```

### 4. Configuration Serveur Web

#### .htaccess (public_html)
```apache
# Frontend SPA
RewriteEngine On
RewriteBase /

# API routes
RewriteRule ^api/(.*)$ router.php [QSA,L]

# Frontend routes (SPA)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# CORS for API
Header always set Access-Control-Allow-Origin "https://yourdomain.com"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
```

## 🐳 Déploiement Docker (Alternatif)

### Dockerfile
```dockerfile
# Multi-stage build
FROM node:18-alpine AS frontend-builder
WORKDIR /app
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

FROM php:8.2-apache AS production

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    sqlite3 libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy backend
COPY src/ /var/www/html/app/src/
COPY composer.json composer.lock /var/www/html/app/
WORKDIR /var/www/html/app
RUN composer install --no-dev --optimize-autoloader

# Copy frontend build
COPY --from=frontend-builder /app/dist/ /var/www/html/

# Configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/

EXPOSE 80
```

### docker-compose.yml
```yaml
version: '3.8'
services:
  app:
    build: .
    ports:
      - "80:80"
    environment:
      - OPENAI_API_KEY=${OPENAI_API_KEY}
      - VIDEO_DOWNLOAD_API_KEY=${VIDEO_DOWNLOAD_API_KEY}
    volumes:
      - ./uploads:/var/www/html/app/uploads
      - ./database:/var/www/html/app/database
```

## 🔐 Sécurité Production

### Variables d'environnement
```php
// config.php
<?php
return [
    'openai' => [
        'api_key' => $_ENV['OPENAI_API_KEY'] ?? '',
    ],
    'database' => [
        'path' => __DIR__ . '/database/production.sqlite'
    ],
    'security' => [
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? '',
        'cors_origin' => $_ENV['CORS_ORIGIN'] ?? 'https://yourdomain.com'
    ]
];
```

### Permissions fichiers
```bash
# cPanel Terminal
chmod 755 public_html/
chmod -R 644 public_html/*
chmod 755 public_html/api/
chmod 600 app/config.php
chmod 755 app/uploads/
chmod 666 app/database.sqlite
```

### Sauvegarde automatique
```bash
#!/bin/bash
# backup.sh - à exécuter via cron

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/username/backups"

# Sauvegarder la base de données
cp app/database.sqlite $BACKUP_DIR/database_$DATE.sqlite

# Sauvegarder les uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz app/uploads/

# Nettoyer les anciennes sauvegardes (>30 jours)
find $BACKUP_DIR -name "*.sqlite" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

## 📅 Maintenance

### Monitoring
```bash
# Vérifier l'espace disque
du -sh app/database.sqlite
du -sh app/uploads/

# Logs d'erreur
tail -f app/logs/error.log

# Nettoyage fichiers temporaires
find app/temp_audio/ -mtime +1 -delete
```

### Mise à jour
```bash
# 1. Sauvegarder
./backup.sh

# 2. Télécharger nouvelle version
git pull origin main

# 3. Mettre à jour dépendances
composer install --no-dev
cd frontend && npm ci && npm run build

# 4. Migrations
php migrate.php

# 5. Vérifier
curl -f https://yourdomain.com/api/health || echo "Erreur détectée"
```

## 📊 Performance

### Optimisations
```apache
# .htaccess - Cache statique
<FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg)$">
    ExpiresActive On
    ExpiresDefault "access plus 1 month"
</FilesMatch>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

## 🌍 Domaines et SSL

### Configuration DNS
```
# Enregistrements DNS
A     yourdomain.com      IP_SERVER
CNAME www.yourdomain.com  yourdomain.com
CNAME api.yourdomain.com  yourdomain.com
```

### SSL (Let's Encrypt via cPanel)
1. **SSL/TLS** dans cPanel
2. **Let's Encrypt** activation
3. **Force HTTPS** redirection

## 🚫 Troubleshooting

### Problèmes courants

**Erreur 500** :
```bash
# Vérifier logs PHP
tail app/logs/error.log

# Vérifier permissions
ls -la app/database.sqlite
```

**Upload échoué** :
```bash
# Vérifier php.ini
php -i | grep upload_max_filesize

# Vérifier espace disque
df -h
```

**API inaccessible** :
```bash
# Tester endpoint
curl -v https://yourdomain.com/api/health

# Vérifier .htaccess
cat .htaccess | grep RewriteRule
```