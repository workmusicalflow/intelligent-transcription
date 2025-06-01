# Guide d'Implémentation des Fonctionnalités de Sécurité

Ce document technique détaille l'implémentation des différentes fonctionnalités de sécurité de l'application Intelligent Transcription. Il est destiné aux développeurs qui souhaitent comprendre ou contribuer au code source de ces fonctionnalités.

## Table des Matières

1. [Protection du Fichier .env](#1-protection-du-fichier-env)
2. [Validation des Entrées](#2-validation-des-entrées)
3. [Sécurité des Fichiers Uploadés](#3-sécurité-des-fichiers-uploadés)
4. [Gestion Standardisée des Erreurs](#4-gestion-standardisée-des-erreurs)
5. [Traitement Asynchrone](#5-traitement-asynchrone)
6. [Échappement des Sorties](#6-échappement-des-sorties)

---

## 1. Protection du Fichier .env

### Objectif de Sécurité

Protéger les informations sensibles comme les clés API stockées dans le fichier .env.

### Implémentation

#### 1.1 Protection par .htaccess

Fichier: `/.htaccess`

```apache
# Protéger les fichiers sensibles
<FilesMatch "^\.env|config\.php\.bak">
    Order allow,deny
    Deny from all
</FilesMatch>

# Désactiver la navigation dans les répertoires
Options -Indexes

# Protéger contre les inclusions de fichiers distants
php_flag allow_url_fopen off
php_flag allow_url_include off
```

Cette configuration empêche l'accès direct aux fichiers .env via le navigateur en bloquant toutes les requêtes HTTP à ces fichiers.

#### 1.2 Gestion des Permissions de Fichiers

Script d'installation:

```bash
# Définir des permissions restrictives sur .env
chmod 600 .env
```

Ces permissions garantissent que seul le propriétaire du fichier (généralement l'utilisateur sous lequel s'exécute le serveur web) peut lire ou modifier le fichier.

#### 1.3 Stockage Hors Répertoire Web

Fichier: `/src/config.php`

```php
// Chemins environnement
define('ENV_DIR', dirname(BASE_DIR));
define('ENV_FILE', ENV_DIR . '/inteligent-transcription-env/.env');

// Fonction pour charger les variables d'environnement
function loadEnvFile($envFilePath) {
    if (file_exists($envFilePath)) {
        $envFile = file_get_contents($envFilePath);
        $lines = explode("\n", $envFile);
        foreach ($lines as $line) {
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (!defined($key) && !empty($key)) {
                    define($key, $value);
                    // Définir également dans $_ENV pour compatibilité
                    $_ENV[$key] = $value;
                    // Définir dans getenv() pour compatibilité
                    putenv("$key=$value");
                }
            }
        }
        return true;
    }
    return false;
}

// Essayer de charger .env en dehors du répertoire web
$envLoaded = loadEnvFile(ENV_FILE);

// Si le fichier .env externe n'existe pas, essayer avec le fichier dans le répertoire du projet
if (!$envLoaded) {
    loadEnvFile(BASE_DIR . '/.env');
}
```

Cette implémentation recherche d'abord le fichier .env dans un répertoire parent, hors de la racine web, avant de chercher dans le répertoire du projet.

#### 1.4 Support en Python

Fichier: `/transcribe.py` et `/paraphrase.py`

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

Cette implémentation Python assure que les scripts peuvent accéder aux variables d'environnement, qu'elles soient stockées dans le répertoire du projet ou en dehors.

#### 1.5 Fichier .env.example

Fichier: `/.env.example`

```
# Configuration de l'application Intelligent Transcription
# NE JAMAIS COMMIT LE FICHIER .env AVEC LES VRAIES CLÉS API !

# Clé API OpenAI pour Whisper et paraphrasage
OPENAI_API_KEY=your_openai_api_key_here

# ID de l'assistant OpenAI pour la paraphrase
PARAPHRASER_ASSISTANT_ID=

# Autres variables...
```

Ce fichier modèle permet aux développeurs de connaître la structure attendue sans exposer de clés réelles.

---

## 2. Validation des Entrées

### Objectif de Sécurité

Prévenir les injections et autres attaques en validant strictement toutes les entrées avant traitement.

### Implémentation

#### 2.1 Classe ValidationUtils

Fichier: `/src/Utils/ValidationUtils.php`

```php
class ValidationUtils {
    // Types de fichiers supportés 
    const SUPPORTED_AUDIO_TYPES = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', ...];
    const SUPPORTED_VIDEO_TYPES = ['video/mp4', 'video/avi', 'video/quicktime', ...];
    
    /**
     * Valide un fichier uploadé
     */
    public static function validateUploadedFile($file, $options = []) {
        // Options par défaut
        $defaults = [
            'max_size' => MAX_UPLOAD_SIZE_BYTES ?? 100 * 1024 * 1024,
            'types' => null // Tous les types supportés par défaut
        ];
        
        $options = array_merge($defaults, $options);
        
        // 1. Vérifier si le fichier existe et s'il n'y a pas d'erreur
        if (!isset($file) || empty($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorCode = isset($file['error']) ? $file['error'] : 'unknown';
            $errorMsg = self::getUploadErrorMessage($errorCode);
            return ['success' => false, 'error' => $errorMsg];
        }
        
        // 2. Vérifier la taille du fichier
        if ($file['size'] > $options['max_size']) {
            $maxSizeMB = $options['max_size'] / (1024 * 1024);
            return [
                'success' => false, 
                'error' => "Le fichier est trop volumineux. Taille maximale autorisée: {$maxSizeMB}MB",
                'advice' => "Veuillez réduire la taille du fichier ou utiliser un outil de compression."
            ];
        }
        
        // 3. Vérifier le type de fichier
        $fileMimeType = mime_content_type($file['tmp_name']);
        $validTypes = $options['types'] ?? array_merge(self::SUPPORTED_AUDIO_TYPES, self::SUPPORTED_VIDEO_TYPES);
        
        if (!in_array($fileMimeType, $validTypes)) {
            $validTypesStr = implode(', ', $validTypes);
            return [
                'success' => false, 
                'error' => "Type de fichier non supporté: {$fileMimeType}",
                'advice' => "Types supportés: {$validTypesStr}"
            ];
        }
        
        // 4. Vérification avancée du contenu (en-têtes de fichiers, etc.)
        $contentValidation = self::validateFileContent($file['tmp_name'], $fileMimeType);
        if (!$contentValidation['success']) {
            return $contentValidation;
        }
        
        // Si tout est OK
        return [
            'success' => true,
            'file_info' => [
                'name' => $file['name'],
                'type' => $fileMimeType,
                'size' => $file['size'],
                'tmp_name' => $file['tmp_name']
            ]
        ];
    }
    
    /**
     * Valide une URL YouTube
     */
    public static function validateYoutubeUrl($url) {
        // Nettoyer l'URL
        $url = trim($url);
        
        // Vérifier si l'URL est valide
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false, 
                'error' => "URL invalide",
                'advice' => "Veuillez entrer une URL YouTube valide."
            ];
        }
        
        // Vérifier si c'est une URL YouTube
        $patterns = [
            '/^https?:\/\/(www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            '/^https?:\/\/(www\.)?youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/^https?:\/\/(www\.)?youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/'
        ];
        
        $isYoutubeUrl = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                $isYoutubeUrl = true;
                break;
            }
        }
        
        if (!$isYoutubeUrl) {
            return [
                'success' => false, 
                'error' => "URL YouTube non valide",
                'advice' => "Veuillez entrer une URL de vidéo YouTube valide."
            ];
        }
        
        // URL valide
        return ['success' => true, 'url' => $url];
    }
    
    /**
     * Validation d'un message texte
     */
    public static function validateTextMessage($message, $options = []) {
        // Options par défaut
        $defaults = [
            'min_length' => 1,
            'max_length' => 10000,
            'allow_html' => false
        ];
        
        $options = array_merge($defaults, $options);
        
        // Nettoyer le message
        $message = trim($message);
        
        // Vérifier la longueur minimale
        if (strlen($message) < $options['min_length']) {
            return [
                'success' => false, 
                'error' => "Message trop court",
                'advice' => "Le message doit contenir au moins {$options['min_length']} caractères."
            ];
        }
        
        // Vérifier la longueur maximale
        if (strlen($message) > $options['max_length']) {
            return [
                'success' => false, 
                'error' => "Message trop long",
                'advice' => "Le message ne doit pas dépasser {$options['max_length']} caractères."
            ];
        }
        
        // Si HTML n'est pas autorisé, nettoyer les balises
        if (!$options['allow_html']) {
            $cleanMessage = strip_tags($message);
            if ($cleanMessage !== $message) {
                return [
                    'success' => false, 
                    'error' => "Le message contient du HTML non autorisé",
                    'advice' => "Veuillez supprimer les balises HTML de votre message."
                ];
            }
        }
        
        // Message valide
        return ['success' => true, 'message' => $message];
    }
    
    // Autres méthodes de validation et utilitaires...
}
```

#### 2.2 Middleware de Validation

Fichier: `/src/Middleware/ValidationMiddleware.php`

```php
class ValidationMiddleware {
    /**
     * Valide les données d'une route spécifique
     */
    public static function validateRoute($controller, $action, $data) {
        $rules = self::getValidationRules($controller, $action);
        
        if (!$rules) {
            // Pas de règles définies pour cette route
            return ['success' => true, 'sanitized' => $data];
        }
        
        return self::validate($data, $rules);
    }
    
    /**
     * Récupère les règles de validation pour une route spécifique
     */
    private static function getValidationRules($controller, $action) {
        $validationRules = [
            'TranscriptionController' => [
                'uploadFile' => [
                    'audio_file' => [
                        'type' => 'file',
                        'required' => true,
                        'options' => [
                            'max_size' => MAX_UPLOAD_SIZE_BYTES,
                            'types' => array_merge(
                                ValidationUtils::SUPPORTED_AUDIO_TYPES,
                                ValidationUtils::SUPPORTED_VIDEO_TYPES
                            )
                        ]
                    ],
                    'language' => [
                        'type' => 'string',
                        'required' => false,
                        'options' => [
                            'default' => 'auto'
                        ]
                    ],
                    'force_language' => [
                        'type' => 'boolean',
                        'required' => false,
                        'options' => [
                            'default' => false
                        ]
                    ]
                ],
                'processYoutubeUrl' => [
                    'youtube_url' => [
                        'type' => 'youtube_url',
                        'required' => true
                    ],
                    'language' => [
                        'type' => 'string',
                        'required' => false,
                        'options' => [
                            'default' => 'auto'
                        ]
                    ],
                    'force_language' => [
                        'type' => 'boolean',
                        'required' => false,
                        'options' => [
                            'default' => false
                        ]
                    ]
                ]
                // Autres actions...
            ],
            // Autres contrôleurs...
        ];
        
        return isset($validationRules[$controller][$action]) ? 
               $validationRules[$controller][$action] : null;
    }
    
    /**
     * Valide les données selon les règles spécifiées
     */
    public static function validate($data, $rules) {
        $sanitizedData = [];
        $errors = [];
        
        // Valider chaque champ selon les règles
        foreach ($rules as $field => $rule) {
            $isRequired = $rule['required'] ?? false;
            $fieldExists = array_key_exists($field, $data);
            
            // Vérifier si le champ est requis
            if ($isRequired && !$fieldExists) {
                $errors[$field] = "Le champ '{$field}' est requis.";
                continue;
            }
            
            // Si le champ n'existe pas mais n'est pas requis, utiliser la valeur par défaut si disponible
            if (!$fieldExists) {
                if (isset($rule['options']['default'])) {
                    $sanitizedData[$field] = $rule['options']['default'];
                }
                continue;
            }
            
            // Valider le champ selon son type
            $validation = self::validateField($data[$field], $rule['type'], $rule['options'] ?? []);
            
            if (!$validation['success']) {
                $errors[$field] = $validation['error'];
                continue;
            }
            
            // Stocker la valeur validée et sanitisée
            $sanitizedData[$field] = $validation['value'];
        }
        
        // Retourner les résultats de la validation
        if (empty($errors)) {
            return ['success' => true, 'sanitized' => $sanitizedData];
        } else {
            return ['success' => false, 'errors' => $errors];
        }
    }
    
    /**
     * Valide un champ spécifique selon son type
     */
    private static function validateField($value, $type, $options = []) {
        switch ($type) {
            case 'file':
                return ValidationUtils::validateUploadedFile($value, $options);
                
            case 'youtube_url':
                return ValidationUtils::validateYoutubeUrl($value);
                
            case 'string':
                return ValidationUtils::validateString($value, $options);
                
            case 'boolean':
                return ValidationUtils::validateBoolean($value, $options);
                
            case 'number':
                return ValidationUtils::validateNumber($value, $options);
                
            case 'email':
                return ValidationUtils::validateEmail($value, $options);
                
            // Autres types de validation...
                
            default:
                return ['success' => false, 'error' => "Type de validation inconnu: {$type}"];
        }
    }
}
```

---

## 3. Sécurité des Fichiers Uploadés

### Objectif de Sécurité

Prévenir les attaques liées aux fichiers uploadés, comme les injections de code ou l'exécution de scripts.

### Implémentation

#### 3.1 Génération de Noms de Fichiers Sécurisés

Fichier: `/src/Utils/FileUtils.php`

```php
/**
 * Génère un nom de fichier sécurisé
 */
public static function secureFileName($originalName, $includeTimestamp = true) {
    // Extraire l'extension
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    
    // S'assurer que l'extension est valide et sécurisée
    if (empty($extension) || !preg_match('/^[a-zA-Z0-9]{1,5}$/', $extension)) {
        $extension = 'bin'; // Valeur par défaut si invalide
    }
    
    // Générer un identifiant aléatoire
    $randomPart = bin2hex(random_bytes(8)); // 16 caractères hexadécimaux
    
    // Ajouter un timestamp si demandé
    $timestamp = $includeTimestamp ? '_' . date('Ymd_His') : '';
    
    // Assembler le nom de fichier sécurisé
    return $randomPart . $timestamp . '.' . $extension;
}
```

#### 3.2 Structure de Stockage Imbriquée

```php
/**
 * Génère un chemin de stockage sécurisé
 */
public static function getSecureStoragePath($baseDir, $fileName, $createDirs = true) {
    // Générer un hash à partir du nom de fichier
    $hash = md5($fileName);
    
    // Utiliser les 2 premiers caractères du hash comme sous-répertoire
    $subDir1 = substr($hash, 0, 2);
    $subDir2 = substr($hash, 2, 2);
    
    // Construire le chemin complet
    $storagePath = $baseDir . '/' . $subDir1 . '/' . $subDir2;
    
    // Créer les répertoires si demandé
    if ($createDirs && !is_dir($storagePath)) {
        mkdir($storagePath, 0755, true);
    }
    
    // Retourner le chemin complet du fichier
    return $storagePath . '/' . $fileName;
}
```

#### 3.3 Stockage Sécurisé des Fichiers Uploadés

```php
/**
 * Stocke un fichier uploadé de manière sécurisée
 */
public static function storeUploadedFile($file, $destinationDir, $options = []) {
    // Options par défaut
    $defaults = [
        'prefix' => 'audio_',
        'add_timestamp' => true,
        'nested_storage' => true,
        'validate_file' => true
    ];
    
    $options = array_merge($defaults, $options);
    
    // Valider le fichier si demandé
    if ($options['validate_file']) {
        $validation = ValidationUtils::validateUploadedFile($file);
        if (!$validation['success']) {
            return $validation;
        }
    }
    
    // Générer un nom de fichier sécurisé
    $originalName = $file['name'];
    $secureName = $options['prefix'] . self::secureFileName($originalName, $options['add_timestamp']);
    
    // Déterminer le chemin de stockage
    $filePath = $options['nested_storage'] 
        ? self::getSecureStoragePath($destinationDir, $secureName)
        : $destinationDir . '/' . $secureName;
    
    // Déplacer le fichier uploadé
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return [
            'success' => false,
            'error' => "Échec du stockage du fichier",
            'advice' => "Vérifiez les permissions du répertoire de destination."
        ];
    }
    
    // Définir les permissions du fichier
    chmod($filePath, 0644); // Lecture pour tous, écriture uniquement pour le propriétaire
    
    // Retourner le chemin du fichier stocké
    return [
        'success' => true,
        'file_path' => $filePath,
        'file_name' => $secureName,
        'original_name' => $originalName
    ];
}
```

---

## 4. Gestion Standardisée des Erreurs

### Objectif de Sécurité

Assurer une gestion cohérente des erreurs pour éviter les fuites d'informations sensibles et améliorer la résilience.

### Implémentation

#### 4.1 Catégories d'Erreurs Standardisées

Fichier: `/src/Utils/PythonErrorUtils.php`

```php
class PythonErrorUtils {
    /**
     * Catégories d'erreurs standardisées
     */
    const ERROR_CATEGORIES = [
        'api_key' => [
            'pattern' => ['api_key', 'authentication', 'auth', 'credential', 'openai key', 'openai api'],
            'message' => "Erreur d'authentification API. Vérifiez votre clé API dans le fichier .env.",
            'advice' => "Veuillez vérifier que votre clé API OpenAI est correctement configurée dans le fichier .env."
        ],
        'file_access' => [
            'pattern' => ['file not found', 'no such file', 'cannot access', 'permission denied', 'not accessible'],
            'message' => "Erreur d'accès au fichier.",
            'advice' => "Vérifiez que le fichier existe et que les permissions sont correctes."
        ],
        'network' => [
            'pattern' => ['network', 'connection', 'timeout', 'connect', 'unreachable', 'connexion'],
            'message' => "Erreur de connexion réseau.",
            'advice' => "Vérifiez votre connexion internet ou réessayez plus tard."
        ],
        'format' => [
            'pattern' => ['format', 'encoding', 'codec', 'unsupported', 'non supporté', 'invalid'],
            'message' => "Format de fichier non supporté ou corrompu.",
            'advice' => "Veuillez utiliser un format audio/vidéo standard comme MP3, WAV, MP4."
        ],
        'quota' => [
            'pattern' => ['quota', 'rate limit', 'exceeded', 'too many', 'limite', 'dépassée'],
            'message' => "Limite d'utilisation de l'API atteinte.",
            'advice' => "Veuillez réessayer plus tard ou augmenter votre quota OpenAI."
        ],
        'media' => [
            'pattern' => ['media error', 'audio error', 'video error', 'corrupt', 'damaged', 'unable to read'],
            'message' => "Erreur dans le fichier média.",
            'advice' => "Le fichier peut être corrompu. Essayez avec un autre fichier."
        ],
        'python' => [
            'pattern' => ['python', 'module', 'import', 'dependency', 'package', 'library'],
            'message' => "Erreur dans l'environnement Python.",
            'advice' => "Exécutez le script setup_env.sh pour reconfigurer l'environnement Python."
        ]
    ];
    
    /**
     * Analyse une erreur pour déterminer sa catégorie
     */
    public static function categorizeError($errorMessage) {
        $errorMessage = strtolower($errorMessage);
        
        foreach (self::ERROR_CATEGORIES as $category => $info) {
            foreach ($info['pattern'] as $pattern) {
                if (strpos($errorMessage, strtolower($pattern)) !== false) {
                    return [
                        'category' => $category,
                        'message' => $info['message'],
                        'advice' => $info['advice'],
                        'original' => $errorMessage
                    ];
                }
            }
        }
        
        // Catégorie par défaut si aucune correspondance
        return [
            'category' => 'unknown',
            'message' => "Une erreur inattendue s'est produite.",
            'advice' => "Veuillez contacter l'administrateur système si le problème persiste.",
            'original' => $errorMessage
        ];
    }
    
    /**
     * Analyse la sortie d'erreur d'un script Python
     */
    public static function parsePythonError($output) {
        // Si la sortie est déjà un JSON valide, l'analyser
        $jsonOutput = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($jsonOutput['error'])) {
                return self::categorizeError($jsonOutput['error']);
            } elseif (isset($jsonOutput['success']) && $jsonOutput['success'] === false) {
                $errorMsg = $jsonOutput['error'] ?? "Erreur non spécifiée";
                return self::categorizeError($errorMsg);
            }
        }
        
        // Si ce n'est pas un JSON valide, chercher des patterns d'erreur courants
        if (strpos($output, 'Traceback') !== false) {
            // Erreur Python standard
            return self::categorizeError($output);
        }
        
        // Par défaut, considérer comme une erreur inconnue
        return [
            'category' => 'unknown',
            'message' => "Une erreur inattendue s'est produite lors de l'exécution du script Python.",
            'advice' => "Veuillez vérifier les logs pour plus de détails.",
            'original' => $output
        ];
    }
    
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
    
    /**
     * Obtient le répertoire de log approprié pour un type d'erreur
     */
    private static function getLogDirectory($errorType) {
        $baseLogDir = BASE_DIR . '/logs';
        
        // Créer le répertoire de logs s'il n'existe pas
        if (!is_dir($baseLogDir)) {
            mkdir($baseLogDir, 0755, true);
        }
        
        // Répertoires spécifiques par type d'erreur
        $typeLogDirs = [
            'api' => $baseLogDir . '/api',
            'python' => $baseLogDir . '/python',
            'upload' => $baseLogDir . '/uploads',
            'processing' => $baseLogDir . '/processing',
            'network' => $baseLogDir . '/network',
            'security' => $baseLogDir . '/security'
        ];
        
        // Utiliser le répertoire spécifique si défini, sinon le répertoire de base
        $logDir = isset($typeLogDirs[$errorType]) ? $typeLogDirs[$errorType] : $baseLogDir;
        
        // Créer le sous-répertoire s'il n'existe pas
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        return $logDir;
    }
}
```

---

## 5. Traitement Asynchrone

### Objectif de Sécurité

Éviter les timeouts HTTP et améliorer l'expérience utilisateur pour les opérations longues durée.

### Implémentation

#### 5.1 AsyncProcessingService

Fichier: `/src/Services/AsyncProcessingService.php`

```php
class AsyncProcessingService {
    // Répertoire de stockage des tâches
    private $jobsDir;
    
    // Statuts possibles des tâches
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELED = 'canceled';
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->jobsDir = BASE_DIR . '/cache/jobs';
        
        // Créer le répertoire de tâches s'il n'existe pas
        if (!is_dir($this->jobsDir)) {
            mkdir($this->jobsDir, 0755, true);
        }
    }
    
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
            'status' => self::STATUS_PENDING,
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
    
    /**
     * Crée une tâche de traitement YouTube
     */
    public function createYoutubeProcessingTask($youtubeUrl, $outputDir, $language = 'auto', $forceLanguage = false, $metadata = []) {
        // Génération d'un ID de tâche unique
        $jobId = uniqid('job_', true);
        
        // Création d'un objet de tâche
        $job = [
            'id' => $jobId,
            'type' => 'youtube_transcription',
            'status' => self::STATUS_PENDING,
            'created_at' => date('Y-m-d H:i:s'),
            'youtube_url' => $youtubeUrl,
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
    
    /**
     * Exécute une tâche spécifique
     */
    public function processTask($jobId) {
        // Récupérer les données de la tâche
        $job = $this->getJobData($jobId);
        
        // Vérifier si la tâche existe
        if (!$job) {
            return ['success' => false, 'error' => "Tâche non trouvée: {$jobId}"];
        }
        
        // Mettre à jour le statut
        $job['status'] = self::STATUS_PROCESSING;
        $job['started_at'] = date('Y-m-d H:i:s');
        $this->saveJobData($jobId, $job);
        
        try {
            // Exécuter la tâche selon son type
            switch ($job['type']) {
                case 'file_transcription':
                    $result = $this->processFileTranscription($job);
                    break;
                    
                case 'youtube_transcription':
                    $result = $this->processYoutubeTranscription($job);
                    break;
                    
                default:
                    throw new Exception("Type de tâche non supporté: {$job['type']}");
            }
            
            // Mettre à jour la tâche avec le résultat
            if ($result['success']) {
                $job['status'] = self::STATUS_COMPLETED;
                $job['result'] = $result;
            } else {
                $job['status'] = self::STATUS_FAILED;
                $job['error'] = $result['error'] ?? "Erreur inconnue";
            }
        } catch (Exception $e) {
            // Gérer les exceptions
            $job['status'] = self::STATUS_FAILED;
            $job['error'] = $e->getMessage();
        }
        
        // Enregistrer la finalisation
        $job['completed_at'] = date('Y-m-d H:i:s');
        $this->saveJobData($jobId, $job);
        
        return $job;
    }
    
    /**
     * Exécute une tâche en arrière-plan
     */
    private function executeTaskInBackground($jobId) {
        // Construire la commande pour exécuter worker.php
        $phpPath = PHP_BINARY;
        $workerScript = BASE_DIR . '/worker.php';
        
        // Construction de la commande
        $command = sprintf(
            '%s %s process_task %s > /dev/null 2>&1 & echo $!',
            escapeshellarg($phpPath),
            escapeshellarg($workerScript),
            escapeshellarg($jobId)
        );
        
        // Exécuter la commande en arrière-plan
        exec($command, $output);
        
        // Stocker le PID du processus
        if (!empty($output[0])) {
            $job = $this->getJobData($jobId);
            if ($job) {
                $job['pid'] = (int)$output[0];
                $this->saveJobData($jobId, $job);
            }
        }
    }
    
    /**
     * Traite une tâche de transcription de fichier
     */
    private function processFileTranscription($job) {
        // Instancier le service de transcription
        $transcriptionService = new TranscriptionService();
        
        // Effectuer la transcription
        return $transcriptionService->transcribeFile(
            $job['file_path'],
            $job['output_dir'],
            $job['language'],
            $job['force_language']
        );
    }
    
    /**
     * Traite une tâche de transcription YouTube
     */
    private function processYoutubeTranscription($job) {
        // Instancier les services nécessaires
        $youtubeService = new YouTubeService();
        $transcriptionService = new TranscriptionService();
        
        // Télécharger la vidéo YouTube
        $downloadResult = $youtubeService->downloadYoutubeVideo(
            $job['youtube_url'],
            TEMP_AUDIO_DIR
        );
        
        if (!$downloadResult['success']) {
            return $downloadResult;
        }
        
        // Effectuer la transcription
        return $transcriptionService->transcribeFile(
            $downloadResult['file_path'],
            $job['output_dir'],
            $job['language'],
            $job['force_language'],
            ['youtube_url' => $job['youtube_url']]
        );
    }
    
    /**
     * Sauvegarde les données d'une tâche
     */
    private function saveJobData($jobId, $data) {
        $filePath = $this->jobsDir . '/' . $jobId . '.json';
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        return true;
    }
    
    /**
     * Récupère les données d'une tâche
     */
    public function getJobData($jobId) {
        $filePath = $this->jobsDir . '/' . $jobId . '.json';
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $content = file_get_contents($filePath);
        return json_decode($content, true);
    }
    
    /**
     * Récupère l'état d'une tâche
     */
    public function getJobStatus($jobId) {
        $job = $this->getJobData($jobId);
        
        if (!$job) {
            return ['success' => false, 'error' => "Tâche non trouvée: {$jobId}"];
        }
        
        return [
            'success' => true,
            'id' => $job['id'],
            'type' => $job['type'],
            'status' => $job['status'],
            'created_at' => $job['created_at'],
            'started_at' => $job['started_at'] ?? null,
            'completed_at' => $job['completed_at'] ?? null,
            'progress' => $job['progress'] ?? 0,
            'error' => $job['error'] ?? null
        ];
    }
    
    /**
     * Annule une tâche en cours
     */
    public function cancelTask($jobId) {
        $job = $this->getJobData($jobId);
        
        if (!$job) {
            return ['success' => false, 'error' => "Tâche non trouvée: {$jobId}"];
        }
        
        // Vérifier si la tâche peut être annulée
        if ($job['status'] === self::STATUS_COMPLETED || $job['status'] === self::STATUS_FAILED) {
            return ['success' => false, 'error' => "La tâche est déjà terminée ou a échoué."];
        }
        
        // Tuer le processus si le PID est disponible
        if (isset($job['pid']) && $job['pid'] > 0) {
            exec("kill " . (int)$job['pid'] . " 2>/dev/null");
        }
        
        // Mettre à jour le statut
        $job['status'] = self::STATUS_CANCELED;
        $job['completed_at'] = date('Y-m-d H:i:s');
        $this->saveJobData($jobId, $job);
        
        return ['success' => true, 'message' => "Tâche annulée avec succès."];
    }
    
    /**
     * Nettoie les tâches anciennes
     */
    public function cleanupOldJobs($daysToKeep = 7) {
        $files = glob($this->jobsDir . '/*.json');
        $now = time();
        $deleteCount = 0;
        
        foreach ($files as $file) {
            $job = json_decode(file_get_contents($file), true);
            
            // Vérifier si la tâche est complétée et ancienne
            if (isset($job['completed_at'])) {
                $completedTime = strtotime($job['completed_at']);
                if (($now - $completedTime) > ($daysToKeep * 86400)) {
                    unlink($file);
                    $deleteCount++;
                }
            } else {
                // Vérifier si c'est une tâche bloquée/abandonnée
                $createdTime = strtotime($job['created_at']);
                if (($now - $createdTime) > (2 * 86400)) { // 2 jours
                    if ($job['status'] === self::STATUS_PENDING || $job['status'] === self::STATUS_PROCESSING) {
                        $job['status'] = self::STATUS_FAILED;
                        $job['error'] = "Tâche abandonnée (timeout)";
                        $job['completed_at'] = date('Y-m-d H:i:s');
                        file_put_contents($file, json_encode($job, JSON_PRETTY_PRINT));
                    }
                }
            }
        }
        
        return ['success' => true, 'deleted' => $deleteCount];
    }
}
```

#### 5.2 Worker Script

Fichier: `/worker.php`

```php
#!/usr/bin/env php
<?php

/**
 * Worker script pour le traitement des tâches en arrière-plan
 * 
 * Usage:
 * php worker.php process_task [job_id]
 * php worker.php process_queue
 * php worker.php cleanup
 */

// Charger l'environnement de l'application
require_once __DIR__ . '/src/bootstrap.php';

// Fonction principale
function main($argc, $argv) {
    // Vérifier les arguments
    if ($argc < 2) {
        echo "Usage: php worker.php [action] [params]\n";
        echo "Actions disponibles:\n";
        echo "  process_task [job_id]  - Traite une tâche spécifique\n";
        echo "  process_queue          - Traite la file d'attente des tâches\n";
        echo "  cleanup                - Nettoie les anciennes tâches\n";
        exit(1);
    }
    
    $action = $argv[1];
    
    // Instancier le service de traitement asynchrone
    $asyncService = new AsyncProcessingService();
    
    switch ($action) {
        case 'process_task':
            if ($argc < 3) {
                echo "Erreur: ID de tâche manquant\n";
                exit(1);
            }
            
            $jobId = $argv[2];
            echo "Traitement de la tâche {$jobId}...\n";
            
            try {
                $result = $asyncService->processTask($jobId);
                echo "Tâche terminée. Statut: {$result['status']}\n";
                
                if ($result['status'] === 'failed') {
                    echo "Erreur: " . ($result['error'] ?? "Inconnue") . "\n";
                    exit(1);
                }
            } catch (Exception $e) {
                echo "Exception: " . $e->getMessage() . "\n";
                exit(1);
            }
            break;
            
        case 'process_queue':
            echo "Traitement de la file d'attente des tâches...\n";
            
            // Rechercher et traiter les tâches en attente
            $jobsDir = BASE_DIR . '/cache/jobs';
            $files = glob($jobsDir . '/*.json');
            
            $processed = 0;
            foreach ($files as $file) {
                $job = json_decode(file_get_contents($file), true);
                
                if ($job['status'] === 'pending') {
                    echo "Traitement de la tâche {$job['id']}...\n";
                    $result = $asyncService->processTask($job['id']);
                    $processed++;
                    
                    echo "Tâche terminée. Statut: {$result['status']}\n";
                }
            }
            
            echo "Traitement terminé. {$processed} tâches traitées.\n";
            break;
            
        case 'cleanup':
            echo "Nettoyage des anciennes tâches...\n";
            
            $daysToKeep = isset($argv[2]) ? (int)$argv[2] : 7;
            $result = $asyncService->cleanupOldJobs($daysToKeep);
            
            echo "Nettoyage terminé. {$result['deleted']} tâches supprimées.\n";
            break;
            
        default:
            echo "Action inconnue: {$action}\n";
            exit(1);
    }
    
    exit(0);
}

// Exécuter le script
main($argc, $argv);
```

---

## 6. Échappement des Sorties

### Objectif de Sécurité

Prévenir les attaques XSS en échappant correctement toutes les sorties affichées aux utilisateurs.

### Implémentation

#### 6.1 Configuration de Twig avec Auto-échappement

Fichier: `/src/Template/TwigManager.php`

```php
class TwigManager {
    private static $instance = null;
    private $twig;
    
    /**
     * Constructeur privé (pattern Singleton)
     */
    private function __construct() {
        $loader = new \Twig\Loader\FilesystemLoader(TEMPLATES_DIR);
        
        $options = [
            'cache' => BASE_DIR . '/cache/twig',
            'auto_reload' => true,
            'debug' => DEBUG_MODE,
            'autoescape' => 'html' // Active l'auto-échappement HTML par défaut
        ];
        
        $this->twig = new \Twig\Environment($loader, $options);
        
        // Ajouter des extensions et filtres
        if (DEBUG_MODE) {
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        }
        
        // Ajouter des fonctions globales
        $this->twig->addGlobal('app_name', 'Intelligent Transcription');
        $this->twig->addGlobal('app_version', '1.0');
        $this->twig->addGlobal('current_year', date('Y'));
        
        // Ajouter des filtres personnalisés
        $this->twig->addFilter(new \Twig\TwigFilter('format_size', [$this, 'formatFileSize']));
        $this->twig->addFilter(new \Twig\TwigFilter('truncate', [$this, 'truncateText']));
    }
    
    /**
     * Obtient l'instance unique de TwigManager (pattern Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Rend un template avec les variables fournies
     */
    public function render($template, $variables = []) {
        try {
            return $this->twig->render($template, $variables);
        } catch (\Exception $e) {
            // Log de l'erreur
            error_log("Erreur Twig: " . $e->getMessage());
            
            if (DEBUG_MODE) {
                return "<h1>Erreur de Template</h1><p>{$e->getMessage()}</p>";
            } else {
                return "<h1>Erreur</h1><p>Une erreur est survenue lors du rendu de la page.</p>";
            }
        }
    }
    
    /**
     * Filtre: Formate une taille de fichier en unités lisibles
     */
    public function formatFileSize($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Filtre: Tronque un texte à une longueur donnée
     */
    public function truncateText($text, $length = 100, $suffix = '...') {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length) . $suffix;
    }
}
```

#### 6.2 Fonction de Sécurité pour les Sorties Directes

Fichier: `/src/Utils/ResponseUtils.php`

```php
class ResponseUtils {
    /**
     * Envoie une réponse JSON
     */
    public static function sendJsonResponse($data, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Redirige vers une URL spécifiée
     */
    public static function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Redirige avec un message d'erreur
     */
    public static function redirectWithError($route, $error, $advice = null) {
        $errorEncoded = urlencode($error);
        $url = "/{$route}?error={$errorEncoded}";
        
        if ($advice) {
            $adviceEncoded = urlencode($advice);
            $url .= "&advice={$adviceEncoded}";
        }
        
        self::redirect($url);
    }
    
    /**
     * Redirige avec un message de succès
     */
    public static function redirectWithSuccess($route, $message) {
        $messageEncoded = urlencode($message);
        $url = "/{$route}?success={$messageEncoded}";
        self::redirect($url);
    }
    
    /**
     * Affiche du texte de manière sécurisée
     */
    public static function escapeHtml($text) {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Échappe les attributs HTML
     */
    public static function escapeHtmlAttr($text) {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Échappe le JavaScript
     */
    public static function escapeJs($text) {
        return json_encode($text, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }
    
    /**
     * Valide et sécurise une URL
     */
    public static function sanitizeUrl($url) {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Vérifier si l'URL est valide
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '#'; // URL invalide, retourner une ancre inoffensive
        }
        
        // Vérifier le protocole (uniquement http et https)
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme']) || !in_array($parsedUrl['scheme'], ['http', 'https'])) {
            return '#';
        }
        
        return $url;
    }
}
```

---

Ce guide d'implémentation fournit les détails techniques nécessaires pour comprendre et maintenir les différentes fonctionnalités de sécurité de l'application. Il sert de référence pour les développeurs qui souhaitent contribuer au code ou adapter ces mesures de sécurité à d'autres projets.