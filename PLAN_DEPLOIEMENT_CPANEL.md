# Plan de déploiement via FileZilla et cPanel

Ce document détaille les étapes pour déployer l'application de transcription intelligente sur un hébergement cPanel, en utilisant FileZilla pour le transfert des fichiers. L'application sera déployée dans le sous-dossier `transcription` à la racine du serveur d'hébergement.

## 1. Préparation de l'environnement local

### 1.1 Optimisation du code pour la production

- Supprimer les fichiers de développement inutiles (tests, documentation de développement, etc.)
- Vérifier que le mode debug est désactivé dans tous les fichiers de configuration
- Optimiser les assets (minification CSS/JS)
- Exécuter Composer avec l'option `--no-dev` pour exclure les dépendances de développement

```bash
composer install --no-dev --optimize-autoloader
```

### 1.2 Préparation des fichiers de configuration

- Créer un fichier `.env.production` avec les variables d'environnement de production
- Vérifier que les chemins dans les fichiers de configuration sont relatifs ou adaptés à l'environnement de production
- Ajuster les paramètres de base de données pour l'environnement de production

## 2. Configuration de cPanel

### 2.1 Création du sous-dossier

- Se connecter au cPanel de l'hébergement
- Accéder au gestionnaire de fichiers
- Créer un dossier `transcription` à la racine du site web (public_html)

### 2.2 Configuration de la base de données

- Accéder à la section "MySQL Databases" dans cPanel
- Créer une nouvelle base de données (ex: `topdigit_transcription`)
- Créer un nouvel utilisateur avec un mot de passe sécurisé
- Attribuer tous les privilèges à l'utilisateur sur la base de données créée
- Noter les informations de connexion pour les utiliser dans la configuration de l'application

### 2.3 Configuration PHP

- Vérifier la version PHP disponible (idéalement PHP 8.0+)
- Accéder à "PHP Selector" ou "MultiPHP Manager" dans cPanel
- Configurer les limites de mémoire et de temps d'exécution:
  - `memory_limit`: au moins 256M
  - `upload_max_filesize`: au moins 100M
  - `post_max_size`: au moins 100M
  - `max_execution_time`: au moins 300
  - `max_input_time`: au moins 300

## 3. Transfert des fichiers via FileZilla

### 3.1 Configuration de la connexion FileZilla

- Ouvrir FileZilla
- Créer une nouvelle connexion avec les informations suivantes:
  - Hôte: `ftp.topdigitalevel.site` (ou l'adresse FTP fournie par l'hébergeur)
  - Nom d'utilisateur: votre nom d'utilisateur cPanel
  - Mot de passe: votre mot de passe cPanel
  - Port: 21 (ou le port FTP spécifié par l'hébergeur)

### 3.2 Transfert des fichiers

- Se connecter au serveur FTP
- Naviguer vers le dossier `public_html/transcription/` sur le serveur distant
- Sélectionner tous les fichiers de l'application locale
- Transférer les fichiers vers le dossier distant
- S'assurer que les permissions des fichiers sont correctement définies:
  - Dossiers: `755` (drwxr-xr-x)
  - Fichiers PHP: `644` (rw-r--r--)
  - Fichiers de configuration sensibles: `600` (rw-------)
  - Dossiers d'upload et de cache: `777` (drwxrwxrwx) ou `775` (drwxrwxr-x)

### 3.3 Exclusion de fichiers sensibles

Ne pas transférer les fichiers suivants:

- `.git/`
- `.env` (utiliser `.env.production` renommé en `.env` sur le serveur)
- `node_modules/`
- Fichiers de développement (`.editorconfig`, `.gitignore`, etc.)
- Fichiers de logs locaux

## 4. Configuration post-déploiement

### 4.1 Configuration du fichier .htaccess

Créer ou modifier le fichier `.htaccess` dans le dossier `transcription/` pour gérer les redirections et les règles de réécriture:

```apache
# Activer le moteur de réécriture
RewriteEngine On

# Définir la base de réécriture
RewriteBase /transcription/

# Rediriger les requêtes vers index.php si le fichier ou dossier n'existe pas
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Protection des fichiers sensibles
<FilesMatch "^\.env|composer\.json|composer\.lock|package\.json|package-lock\.json|README\.md|\.gitignore">
    Order allow,deny
    Deny from all
</FilesMatch>

# Compression GZIP
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json
</IfModule>

# Cache des ressources statiques
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresDefault "access plus 2 days"
</IfModule>
```

### 4.2 Configuration de la base de données

Modifier le fichier de configuration de la base de données avec les informations créées précédemment:

```php
// config.php ou .env
DB_HOST = 'localhost';
DB_NAME = 'topdigit_transcription';
DB_USER = 'votre_utilisateur_db';
DB_PASS = 'votre_mot_de_passe_db';
```

### 4.3 Création des dossiers avec permissions spéciales

Créer et configurer les permissions des dossiers suivants:

```bash
# Dossiers pour les uploads et fichiers temporaires
mkdir -p uploads
mkdir -p temp_audio
mkdir -p results
mkdir -p cache

# Définir les permissions
chmod 775 uploads
chmod 775 temp_audio
chmod 775 results
chmod 775 cache
```

### 4.4 Installation des dépendances sur le serveur (si nécessaire)

Si vous n'avez pas transféré le dossier `vendor`, vous devrez installer les dépendances sur le serveur:

```bash
# Se connecter en SSH (si disponible)
ssh utilisateur@topdigitalevel.site

# Naviguer vers le dossier de l'application
cd public_html/transcription

# Installer les dépendances
composer install --no-dev --optimize-autoloader
```

Si l'accès SSH n'est pas disponible, vous pouvez utiliser un plugin Composer pour cPanel ou transférer le dossier `vendor` directement.

## 5. Tests post-déploiement

### 5.1 Vérification de base

- Accéder à `https://topdigitalevel.site/transcription/`
- Vérifier que la page d'accueil s'affiche correctement
- Vérifier que les styles et scripts sont chargés correctement

### 5.2 Tests fonctionnels

- Tester le téléchargement d'un fichier audio
- Tester la transcription
- Tester la paraphrase
- Tester l'export vers Google Docs (si implémenté)
- Vérifier que les fichiers sont correctement stockés dans les dossiers appropriés

### 5.3 Vérification des logs

- Vérifier les logs d'erreur PHP dans cPanel (section "Error Log")
- Vérifier les logs d'accès pour détecter d'éventuels problèmes

## 6. Optimisation et sécurité

### 6.1 Mise en place d'un certificat SSL

- Accéder à la section "SSL/TLS" dans cPanel
- Installer un certificat Let's Encrypt pour `topdigitalevel.site`
- Configurer la redirection HTTPS dans le fichier `.htaccess`:

```apache
# Redirection HTTP vers HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 6.2 Configuration des sauvegardes

- Configurer des sauvegardes automatiques dans cPanel
- Planifier des sauvegardes de la base de données et des fichiers
- Définir une stratégie de rétention des sauvegardes

### 6.3 Mise en place d'un système de surveillance

- Configurer des alertes en cas de temps d'arrêt
- Mettre en place un système de surveillance des performances
- Configurer des notifications en cas d'erreurs critiques

## 7. Documentation et maintenance

### 7.1 Documentation du déploiement

- Documenter les étapes de déploiement réalisées
- Noter les configurations spécifiques à l'environnement
- Créer un guide de dépannage pour les problèmes courants

### 7.2 Plan de maintenance

- Définir un calendrier de mises à jour régulières
- Planifier des vérifications périodiques des performances
- Établir une procédure pour les mises à jour d'urgence

### 7.3 Formation des utilisateurs

- Créer une documentation utilisateur
- Former les administrateurs à la gestion de l'application
- Mettre en place un système de support pour les utilisateurs
