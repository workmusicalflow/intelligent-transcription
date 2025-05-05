<?php

namespace Utils;

/**
 * Utility class for handling errors in PHP-Python communication
 */
class PythonErrorUtils
{
    /**
     * Standard categories of Python errors for better user-friendly messages
     */
    const ERROR_CATEGORIES = [
        'api_key' => [
            'pattern' => [
                'api_key', 'authentication', 'auth', 'credential', 'OPENAI_API_KEY',
                'No API key', 'Invalid API key'
            ],
            'message' => "Erreur d'authentification API. Vérifiez votre clé API dans le fichier .env.",
            'advice' => "Veuillez vérifier que votre clé API OpenAI est correctement configurée dans le fichier .env."
        ],
        'file_access' => [
            'pattern' => [
                'permission denied', 'no such file', "file doesn't exist", 'cannot open',
                'no such directory', 'not a directory', 'file not found'
            ],
            'message' => "Erreur d'accès fichier. Le système ne peut pas accéder au fichier.",
            'advice' => "Vérifiez les permissions et l'existence des répertoires de travail."
        ],
        'network' => [
            'pattern' => [
                'network', 'connection', 'timeout', 'unreachable', 'refused', 'reset',
                'ConnectionError', 'Timeout', 'ConnectTimeout'
            ],
            'message' => "Erreur réseau. Impossible de se connecter à l'API.",
            'advice' => "Vérifiez votre connexion internet et réessayez ultérieurement."
        ],
        'format' => [
            'pattern' => [
                'json', 'decode', 'encode', 'format', 'syntax', 'invalid', 'malformed',
                'JSONDecodeError', 'SyntaxError'
            ],
            'message' => "Erreur de format de données.",
            'advice' => "Le format des données échangées entre PHP et Python est incorrect."
        ],
        'quota' => [
            'pattern' => [
                'quota', 'limit', 'exceeded', 'rate limit', 'too many', 'usage cap',
                'RateLimitError'
            ],
            'message' => "Limite d'utilisation atteinte sur l'API.",
            'advice' => "Veuillez réessayer plus tard ou augmenter vos limites d'API."
        ],
        'media' => [
            'pattern' => [
                'unsupported file', 'invalid audio', 'corrupt', 'codec', 'media format',
                'not a valid', 'invalid format'
            ],
            'message' => "Format média non supporté.",
            'advice' => "Veuillez utiliser un format audio/vidéo standard (MP3, WAV, MP4, etc.)."
        ],
        'python' => [
            'pattern' => [
                'ImportError', 'ModuleNotFoundError', 'PackageNotFoundError', 'not installed', 
                'not found in PATH', 'command not found', 'module'
            ],
            'message' => "Erreur d'environnement Python.",
            'advice' => "L'environnement Python n'est pas correctement configuré. Contactez l'administrateur."
        ]
    ];

    /**
     * Analyze Python error message and return user-friendly message
     * 
     * @param string $errorOutput Raw error message from Python
     * @param string $processName Name of the process (transcription, paraphrase, etc.)
     * @return array Error information with categorized message
     */
    public static function analyzePythonError($errorOutput, $processName = 'traitement')
    {
        $errorOutput = strtolower($errorOutput);
        $pythonError = self::extractJsonError($errorOutput) ?? $errorOutput;
        
        // Try to identify the error category
        foreach (self::ERROR_CATEGORIES as $category => $info) {
            foreach ($info['pattern'] as $pattern) {
                if (stripos($pythonError, $pattern) !== false) {
                    return [
                        'success' => false,
                        'error' => $info['message'],
                        'details' => $pythonError,
                        'category' => $category,
                        'advice' => $info['advice']
                    ];
                }
            }
        }
        
        // Default generic error message
        return [
            'success' => false,
            'error' => "Erreur lors du $processName.",
            'details' => $pythonError,
            'category' => 'unknown',
            'advice' => "Veuillez réessayer ou contacter l'administrateur si le problème persiste."
        ];
    }
    
    /**
     * Extract JSON error from Python output if possible
     * 
     * @param string $output Output from Python process
     * @return string|null Extracted error message or null if not found
     */
    public static function extractJsonError($output)
    {
        // Try to extract a JSON object from the output
        if (preg_match('/{.*}/', $output, $matches)) {
            $jsonString = $matches[0];
            $data = json_decode($jsonString, true);
            
            if ($data && isset($data['error'])) {
                return $data['error'];
            }
            
            if ($data && isset($data['success']) && $data['success'] === false) {
                return $data['message'] ?? 'Unknown error in JSON response';
            }
        }
        
        return null;
    }
    
    /**
     * Process Python process execution with standardized error handling
     * 
     * @param string $command Command to execute
     * @param string $processName Name of the process for error messages
     * @param string|null $outputFile Path to output file to check
     * @return array Result of the process execution
     */
    public static function executePythonProcess($command, $processName = 'traitement', $outputFile = null)
    {
        // Execute the command and capture the output
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (!is_resource($process)) {
            return [
                'success' => false,
                'error' => "Impossible de démarrer le processus de $processName",
                'category' => 'process',
                'advice' => "Vérifiez que Python est correctement installé et configuré."
            ];
        }
        
        // Read the standard output
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        // Read the error output
        $error_output = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        
        // Close the process
        $return_value = proc_close($process);
        
        // Log debugging information
        $debug_info = [
            'command' => $command,
            'output' => $output,
            'error_output' => $error_output,
            'return_value' => $return_value,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log to general process log
        $logFile = BASE_DIR . '/logs/python/python_process_' . strtolower(str_replace(' ', '_', $processName)) . '.log';
        file_put_contents($logFile, print_r($debug_info, true), FILE_APPEND);
        
        // If there's an error, log to category-specific directory
        if ($return_value !== 0 || !empty($error_output)) {
            $category = 'general';
            
            // Analyse pour déterminer la catégorie
            foreach (self::ERROR_CATEGORIES as $cat => $info) {
                foreach ($info['pattern'] as $pattern) {
                    if (stripos($error_output, $pattern) !== false) {
                        $category = $cat;
                        break 2;  // Sortir des deux boucles
                    }
                }
            }
            
            // Créer le fichier de log catégorisé
            $categoryLog = BASE_DIR . '/logs/python/' . $category . '_errors/' . 
                           date('Ymd_His') . '_' . strtolower(str_replace(' ', '_', $processName)) . '.log';
            file_put_contents($categoryLog, print_r($debug_info, true));
        }
        
        // If output file is specified, try to read it
        if ($outputFile && file_exists($outputFile)) {
            $fileContent = file_get_contents($outputFile);
            $result = json_decode($fileContent, true);
            
            if ($result && isset($result['success'])) {
                if ($result['success']) {
                    return $result;
                } else {
                    // Process failed, analyze the error
                    return self::analyzePythonError($result['error'] ?? $error_output, $processName);
                }
            }
        }
        
        // Try to parse JSON from stdout
        $result = json_decode($output, true);
        
        if ($result && isset($result['success'])) {
            if ($result['success']) {
                return $result;
            } else {
                return self::analyzePythonError($result['error'] ?? $error_output, $processName);
            }
        }
        
        // If we're here, we couldn't parse a proper JSON response
        if (!empty($error_output)) {
            // There was an error in the Python process
            return self::analyzePythonError($error_output, $processName);
        }
        
        // Default error if we couldn't determine what went wrong
        return [
            'success' => false,
            'error' => "Erreur lors du $processName",
            'details' => "Réponse invalide du script Python",
            'category' => 'unknown',
            'advice' => "Vérifiez les logs pour plus d'informations."
        ];
    }
    
    /**
     * Récupère toutes les erreurs Python récentes par catégorie
     * 
     * @param string|null $category Catégorie d'erreur spécifique ou null pour toutes
     * @param int $limit Nombre maximal d'erreurs à récupérer
     * @return array Erreurs regroupées par catégorie
     */
    public static function getRecentErrors($category = null, $limit = 10)
    {
        $errors = [];
        
        // Définir les répertoires à parcourir
        if ($category) {
            $categories = [$category];
        } else {
            $categories = array_keys(self::ERROR_CATEGORIES);
            $categories[] = 'general';
        }
        
        // Parcourir chaque catégorie
        foreach ($categories as $cat) {
            $errorDir = BASE_DIR . '/logs/python/' . $cat . '_errors/';
            
            if (!is_dir($errorDir)) {
                continue;
            }
            
            $files = glob($errorDir . '*.log');
            // Trier par date de modification (plus récent en premier)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // Limiter le nombre de fichiers
            $files = array_slice($files, 0, $limit);
            
            $errors[$cat] = [];
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $errors[$cat][] = [
                    'file' => basename($file),
                    'timestamp' => date('Y-m-d H:i:s', filemtime($file)),
                    'content' => $content
                ];
            }
        }
        
        return $errors;
    }
    
    /**
     * Nettoie les anciens fichiers de log (plus de 7 jours)
     */
    public static function cleanupOldLogs()
    {
        $basePath = BASE_DIR . '/logs/python/';
        $categories = array_keys(self::ERROR_CATEGORIES);
        $categories[] = 'general';
        
        foreach ($categories as $category) {
            $errorDir = $basePath . $category . '_errors/';
            
            if (!is_dir($errorDir)) {
                continue;
            }
            
            $files = glob($errorDir . '*.log');
            $now = time();
            
            foreach ($files as $file) {
                // Supprimer les fichiers plus vieux que 7 jours
                if ($now - filemtime($file) > 604800) {
                    unlink($file);
                }
            }
        }
        
        // Nettoyer également les fichiers de logs généraux
        $generalLogs = glob($basePath . '*.log');
        $now = time();
        
        foreach ($generalLogs as $file) {
            // Conserver uniquement les 10 derniers Mo des fichiers de logs généraux
            $size = filesize($file);
            if ($size > 10 * 1024 * 1024) {
                $content = file_get_contents($file);
                $truncated = substr($content, -10 * 1024 * 1024);
                file_put_contents($file, $truncated);
            }
        }
    }
}