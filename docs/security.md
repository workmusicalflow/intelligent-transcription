# Sécurité de l'Application

Ce document décrit les mesures de sécurité implémentées dans l'application Intelligent Transcription pour protéger les données sensibles, assurer l'intégrité du système et prévenir les vulnérabilités courantes.

## Table des Matières

1. [Gestion des Variables d'Environnement](#1-gestion-des-variables-denvironnement)
2. [Validation des Entrées](#2-validation-des-entrées)
3. [Sécurité des Fichiers Uploadés](#3-sécurité-des-fichiers-uploadés)
4. [Gestion des Erreurs](#4-gestion-des-erreurs)
5. [Traitement Asynchrone](#5-traitement-asynchrone)
6. [Communication PHP-Python](#6-communication-php-python)
7. [Bonnes Pratiques Générales](#7-bonnes-pratiques-générales)

## 1. Gestion des Variables d'Environnement

### Protection du Fichier .env

Le fichier `.env` contient des données sensibles comme les clés API. Les mesures suivantes ont été mises en place pour sa protection :

#### Options de Stockage

**Option 1 (Recommandée) : Stockage hors du répertoire web**
- Le fichier `.env` est stocké dans un répertoire parent hors de la racine web
- Chemin : `/chemin/vers/inteligent-transcription-env/.env`

**Option 2 : Dans le répertoire du projet avec protections**
- Protection par règles `.htaccess`
- Permissions de fichier strictes (600 - uniquement lecture/écriture par le propriétaire)

#### Implémentation dans le Code

**PHP**
```php
// Fonction pour charger les variables d'environnement
function loadEnvFile($envFilePath) {
    if (file_exists($envFilePath)) {
        $envFile = file_get_contents($envFilePath);
        $lines = explode("\n", $envFile);
        foreach ($lines as $line) {
            // Code de traitement...
        }
        return true;
    }
    return false;
}

// Essayer de charger .env en dehors du répertoire web d'abord
$envLoaded = loadEnvFile(ENV_FILE);

// Si le fichier .env externe n'existe pas, essayer avec le fichier dans le répertoire du projet
if (!$envLoaded) {
    loadEnvFile(BASE_DIR . '/.env');
}
```

**Python**
```python
# Charger les variables d'environnement depuis différents emplacements possibles
env_paths = [
    os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(os.path.dirname(__file__)))), 
                'inteligent-transcription-env', '.env'),
    os.path.join(os.path.dirname(os.path.abspath(__file__)), '.env')
]

for env_path in env_paths:
    if os.path.exists(env_path):
        load_dotenv(env_path)
        break
```

#### Configuration .htaccess

```apache
# Protéger les fichiers sensibles
<FilesMatch "^\.env|config\.php\.bak">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protéger les répertoires sensibles
<DirectoryMatch "^/(?:logs|cache|venv)/">
    Order allow,deny
    Deny from all
</DirectoryMatch>

# Désactiver la navigation dans les répertoires
Options -Indexes

# Protéger contre les inclusions de fichiers distants
php_flag allow_url_fopen off
php_flag allow_url_include off
```

### Fichier .env.example

Un fichier `.env.example` a été créé comme modèle pour les développeurs, contenant la structure attendue sans les valeurs sensibles :

```
# Configuration de l'application Intelligent Transcription
# NE JAMAIS COMMIT LE FICHIER .env AVEC LES VRAIES CLÉS API !

# Clé API OpenAI pour Whisper et paraphrasage
OPENAI_API_KEY=your_openai_api_key_here

# ID de l'assistant OpenAI pour la paraphrase
PARAPHRASER_ASSISTANT_ID=

# Clé API pour video-download-api.com
VIDEO_DOWNLOAD_API_KEY=your_video_download_api_key_here

# Configuration de l'environnement
APP_ENV=development
APP_DEBUG=true
...
```

## 2. Validation des Entrées

### Class ValidationUtils

Une classe centralisée de validation a été implémentée pour assurer que toutes les entrées utilisateur sont vérifiées avant traitement :

```php
class ValidationUtils {
    // Types de fichiers supportés 
    const SUPPORTED_AUDIO_TYPES = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', ...];
    const SUPPORTED_VIDEO_TYPES = ['video/mp4', 'video/avi', 'video/quicktime', ...];
    
    /**
     * Valide un fichier uploadé
     */
    public static function validateUploadedFile($file, $options = []) {
        // Validation du type de fichier, taille, intégrité, etc.
    }
    
    /**
     * Valide une URL YouTube
     */
    public static function validateYoutubeUrl($url) {
        // Validation du format d'URL YouTube
    }
    
    /**
     * Valide un message texte
     */
    public static function validateTextMessage($message, $options = []) {
        // Validation du contenu texte
    }
    
    // Autres méthodes de validation spécifiques...
}
```

### Middleware de Validation

Un middleware de validation assure que toutes les requêtes sont validées de manière cohérente avant d'atteindre les contrôleurs :

```php
class ValidationMiddleware {
    /**
     * Valide les données d'une route spécifique
     */
    public static function validateRoute($controller, $action, $data) {
        $rules = self::getValidationRules($controller, $action);
        
        if (!$rules) {
            return ['success' => true, 'sanitized' => $data];
        }
        
        return self::validate($data, $rules);
    }
    
    // Règles de validation par contrôleur/action
    private static function getValidationRules($controller, $action) {
        $validationRules = [
            'TranscriptionController' => [
                'uploadFile' => [ /* règles */ ],
                // Autres actions...
            ],
            // Autres contrôleurs...
        ];
        
        // Retourne les règles spécifiques
    }
}
```

## 3. Sécurité des Fichiers Uploadés

La sécurité des fichiers uploadés est assurée par plusieurs mesures :

### Noms de Fichiers Aléatoires

```php
public static function secureFileName($originalName, $includeTimestamp = true) {
    // Extract file extension
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    
    // Ensure extension is valid and secure
    if (empty($extension) || !preg_match('/^[a-zA-Z0-9]{1,5}$/', $extension)) {
        $extension = 'bin'; // Default if invalid
    }
    
    // Generate random identifier
    $randomPart = bin2hex(random_bytes(8)); // 16 hex characters
    
    // Optionally add timestamp
    $timestamp = $includeTimestamp ? '_' . date('Ymd_His') : '';
    
    // Assemble secure filename
    return $randomPart . $timestamp . '.' . $extension;
}
```

### Structure de Répertoires Imbriqués

```php
public static function getSecureStoragePath($baseDir, $fileName, $createDirs = true) {
    // Generate hash from filename
    $hash = md5($fileName);
    
    // Use first 2 characters of hash as subdirectory
    $subDir1 = substr($hash, 0, 2);
    $subDir2 = substr($hash, 2, 2);
    
    // Build complete path
    $storagePath = $baseDir . '/' . $subDir1 . '/' . $subDir2;
    
    // Create directories if requested
    if ($createDirs && !is_dir($storagePath)) {
        mkdir($storagePath, 0755, true);
    }
    
    // Return complete path to file
    return $storagePath . '/' . $fileName;
}
```

### Validation du Contenu

Les fichiers sont validés en profondeur :
- Type MIME vérification
- Analyse des en-têtes de fichiers
- Vérification des extensions
- Limites de taille configurables
- Détection de contenu malveillant

## 4. Gestion des Erreurs

Un système standardisé de gestion des erreurs a été implémenté pour capturer et gérer efficacement les erreurs, en particulier dans la communication PHP-Python.

### Catégories d'Erreurs Normalisées

```php
/**
 * Catégories d'erreurs standardisées
 */
const ERROR_CATEGORIES = [
    'api_key' => [
        'pattern' => ['api_key', 'authentication', 'auth', 'credential'],
        'message' => "Erreur d'authentification API. Vérifiez votre clé API.",
        'advice' => "Veuillez vérifier que votre clé API est correctement configurée."
    ],
    'file_access' => [
        'pattern' => ['file not found', 'no such file', 'cannot access', 'permission denied'],
        'message' => "Erreur d'accès au fichier.",
        'advice' => "Vérifiez que le fichier existe et que les permissions sont correctes."
    ],
    // Autres catégories...
];
```

### Journalisation des Erreurs

```php
/**
 * Journalise une erreur dans un fichier de log
 */
public static function logError($errorType, $errorMessage, $context = []) {
    $logDir = self::getLogDirectory($errorType);
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$errorType}] {$errorMessage}";
    
    // Ajouter le contexte si disponible
    if (!empty($context)) {
        $logEntry .= " | Context: " . json_encode($context);
    }
    
    // Écrire dans le fichier de log spécifique à ce type d'erreur
    file_put_contents(
        $logDir . "/error_{$errorType}.log", 
        $logEntry . PHP_EOL, 
        FILE_APPEND
    );
}
```

## 5. Traitement Asynchrone

Pour les opérations longues comme la transcription de fichiers volumineux, un système de traitement asynchrone a été mis en place :

### AsyncProcessingService

```php
/**
 * Crée une tâche de traitement de fichier
 */
public function createFileProcessingTask($filePath, $outputDir, $language = 'auto', $forceLanguage = false, $metadata = []) {
    // Génération d'un ID de tâche unique
    $jobId = uniqid('job_', true);
    
    // Création d'un objet de tâche
    $job = [
        'id' => $jobId,
        'type' => 'file_transcription',
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'file_path' => $filePath,
        'output_dir' => $outputDir,
        'language' => $language,
        'force_language' => $forceLanguage,
        'metadata' => $metadata
    ];
    
    // Enregistrement de la tâche dans le système
    $this->saveJobData($jobId, $job);
    
    // Lancement du traitement en arrière-plan
    $this->executeTaskInBackground($jobId);
    
    return ['success' => true, 'job_id' => $jobId];
}
```

### Worker en Arrière-plan

Un script worker.php exécute les tâches en arrière-plan, évitant les timeouts HTTP et améliorant l'expérience utilisateur pour les traitements longs.

## 6. Communication PHP-Python

La communication sécurisée entre PHP et Python est assurée par un système standardisé :

```php
/**
 * Exécute un script Python avec des arguments sécurisés
 */
public static function executePythonScript($scriptPath, $args = [], $timeoutSeconds = 60) {
    // Validation du chemin du script
    if (!file_exists($scriptPath)) {
        return ['success' => false, 'error' => "Script not found: {$scriptPath}"];
    }
    
    // Construction de la commande avec échappement des arguments
    $command = PYTHON_PATH . ' ' . escapeshellarg($scriptPath);
    
    // Ajout sécurisé des arguments
    foreach ($args as $key => $value) {
        if (is_bool($value)) {
            // Option booléenne (flag)
            if ($value) {
                $command .= ' --' . escapeshellarg($key);
            }
        } else {
            // Option avec valeur
            $command .= ' --' . escapeshellarg($key) . ' ' . escapeshellarg($value);
        }
    }
    
    // Exécution sécurisée avec capture du résultat
    $descriptorSpec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"]   // stderr
    ];
    
    $process = proc_open($command, $descriptorSpec, $pipes);
    
    if (!is_resource($process)) {
        return ['success' => false, 'error' => "Failed to execute Python script"];
    }
    
    // Configuration des timeouts et lecture des résultats
    // ...
    
    // Fermeture du processus et analyse du résultat
    $exitCode = proc_close($process);
    $result = json_decode($output, true);
    
    // Vérification et retour standardisé
    // ...
    
    return $result;
}
```

## 7. Bonnes Pratiques Générales

Plusieurs bonnes pratiques de sécurité sont implémentées globalement :

### Échappement des Sorties

- Utilisation de Twig avec auto-échappement activé par défaut
- Fonction `htmlspecialchars` pour les sorties directes en PHP

### Protection contre les Attaques CSRF

- Tokens de formulaire
- Validation des origines de requête

### Gestion des Sessions

- Configuration sécurisée des cookies (httpOnly, secure)
- Régénération des IDs de session

### Contrôle d'Accès

- Validation des permissions avant chaque action sensible
- Isolation des fonctionnalités d'administration

### Gestion des Dépendances

- Bibliothèques tierces maintenues à jour
- Audit régulier des vulnérabilités

---

## Références

Pour plus d'informations sur l'implémentation spécifique de ces mesures de sécurité, consultez les fichiers suivants :

- `/src/Utils/ValidationUtils.php` - Validation des entrées
- `/src/Utils/FileUtils.php` - Gestion sécurisée des fichiers
- `/src/Utils/PythonErrorUtils.php` - Gestion des erreurs
- `/src/Services/AsyncProcessingService.php` - Traitement asynchrone
- `/.htaccess` - Protection des ressources sensibles
- `/ENVIRONMENT_SETUP.md` - Configuration de l'environnement