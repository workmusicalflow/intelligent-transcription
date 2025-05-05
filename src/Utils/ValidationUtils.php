<?php

namespace Utils;

/**
 * Classe utilitaire pour la validation des entrées
 */
class ValidationUtils
{
    /**
     * Types de fichiers supportés pour les uploads
     */
    const SUPPORTED_AUDIO_TYPES = [
        'audio/mpeg' => '.mp3',
        'audio/mp3' => '.mp3',
        'audio/wav' => '.wav',
        'audio/wave' => '.wav', 
        'audio/x-wav' => '.wav',
        'audio/x-pn-wav' => '.wav',
        'audio/ogg' => '.ogg',
        'audio/vorbis' => '.ogg',
        'audio/aac' => '.aac',
        'audio/mp4' => '.m4a',
        'audio/x-m4a' => '.m4a',
        'audio/webm' => '.webm'
    ];
    
    /**
     * Types de fichiers vidéo supportés
     */
    const SUPPORTED_VIDEO_TYPES = [
        'video/mp4' => '.mp4',
        'video/mpeg' => '.mpeg',
        'video/webm' => '.webm',
        'video/quicktime' => '.mov',
        'video/x-msvideo' => '.avi',
        'video/x-ms-wmv' => '.wmv'
    ];
    
    /**
     * Valide un fichier téléchargé
     * 
     * @param array $file Données du fichier $_FILES['file']
     * @param array $options Options de validation
     *                      - max_size: Taille maximale en bytes
     *                      - types: Types MIME acceptés (null = tous les types audio/vidéo)
     * @return array Résultat de validation avec 'valid' et 'error'
     */
    public static function validateUploadedFile($file, $options = [])
    {
        // Options par défaut
        $defaults = [
            'max_size' => MAX_UPLOAD_SIZE_BYTES ?? 100 * 1024 * 1024, // 100 MB par défaut
            'types' => null // Tous les types audio/vidéo supportés par défaut
        ];
        
        $options = array_merge($defaults, $options);
        
        // Vérifier les erreurs de téléchargement
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite définie dans php.ini (upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite définie dans le formulaire HTML (MAX_FILE_SIZE)',
                UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
                UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier'
            ];
            
            $error_code = $file['error'];
            $error_message = $error_messages[$error_code] ?? 'Erreur inconnue (code: ' . $error_code . ')';
            
            return [
                'valid' => false,
                'error' => $error_message
            ];
        }
        
        // Vérifier la taille du fichier
        if ($file['size'] > $options['max_size']) {
            $max_size_mb = round($options['max_size'] / (1024 * 1024), 1);
            return [
                'valid' => false,
                'error' => "Le fichier dépasse la limite de taille autorisée ({$max_size_mb} MB)"
            ];
        }
        
        // Vérifier si un fichier a bien été téléchargé
        if ($file['size'] === 0) {
            return [
                'valid' => false,
                'error' => 'Le fichier est vide'
            ];
        }
        
        // Vérifier le type MIME
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        
        // Si aucun type spécifique n'est fourni, accepter tous les types audio/vidéo supportés
        if ($options['types'] === null) {
            $allowed_types = array_merge(self::SUPPORTED_AUDIO_TYPES, self::SUPPORTED_VIDEO_TYPES);
        } else {
            $allowed_types = $options['types'];
        }
        
        if (!array_key_exists($mime_type, $allowed_types)) {
            return [
                'valid' => false,
                'error' => "Type de fichier non supporté: {$mime_type}. Types acceptés: " . implode(', ', array_keys($allowed_types))
            ];
        }
        
        // Analyse de sécurité supplémentaire du fichier
        if (!self::isSecureFile($file['tmp_name'])) {
            return [
                'valid' => false, 
                'error' => 'Le fichier contient potentiellement du contenu dangereux'
            ];
        }
        
        // Si tout est bon
        return [
            'valid' => true,
            'mime_type' => $mime_type,
            'extension' => $allowed_types[$mime_type]
        ];
    }
    
    /**
     * Valide une URL YouTube
     * 
     * @param string $url URL à valider
     * @return array Résultat de validation avec 'valid' et 'video_id' si valide
     */
    public static function validateYoutubeUrl($url)
    {
        // Nettoyer l'URL
        $url = trim($url);
        
        // Vérifier si l'URL est vide
        if (empty($url)) {
            return [
                'valid' => false,
                'error' => 'L\'URL YouTube est vide'
            ];
        }
        
        // Convertir les URLs mobiles en URLs standard
        $url = str_replace('m.youtube.com', 'www.youtube.com', $url);
        $url = str_replace('youtu.be/', 'www.youtube.com/watch?v=', $url);
        
        // Pattern pour les URLs YouTube standard et les URLs YouTube Shorts
        $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtube\.com\/shorts\/|youtube\.com\/v\/|youtube\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})(\S*)?$/';
        
        if (preg_match($pattern, $url, $matches)) {
            return [
                'valid' => true,
                'video_id' => $matches[4],
                'normalized_url' => 'https://www.youtube.com/watch?v=' . $matches[4]
            ];
        }
        
        return [
            'valid' => false,
            'error' => 'Format d\'URL YouTube invalide. Exemple valide: https://www.youtube.com/watch?v=VIDEO_ID'
        ];
    }
    
    /**
     * Valide un message texte
     * 
     * @param string $message Message à valider
     * @param array $options Options de validation
     *                      - min_length: Longueur minimale (défaut: 1)
     *                      - max_length: Longueur maximale (défaut: 10000)
     *                      - strip_tags: Supprimer les balises HTML (défaut: true)
     * @return array Résultat de validation avec 'valid' et 'sanitized' si valide
     */
    public static function validateTextMessage($message, $options = [])
    {
        // Options par défaut
        $defaults = [
            'min_length' => 1,
            'max_length' => 10000,
            'strip_tags' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        // Nettoyer le message
        $message = trim($message);
        
        // Supprimer les balises HTML si demandé
        if ($options['strip_tags']) {
            $message = strip_tags($message);
        }
        
        // Vérifier si le message est vide
        if (empty($message) || mb_strlen($message) < $options['min_length']) {
            return [
                'valid' => false,
                'error' => 'Le message est trop court. Minimum ' . $options['min_length'] . ' caractères requis.'
            ];
        }
        
        // Vérifier la longueur maximale
        if (mb_strlen($message) > $options['max_length']) {
            return [
                'valid' => false,
                'error' => 'Le message est trop long. Maximum ' . $options['max_length'] . ' caractères autorisés.'
            ];
        }
        
        // Si tout est bon
        return [
            'valid' => true,
            'sanitized' => $message
        ];
    }
    
    /**
     * Valide les paramètres de transcription
     * 
     * @param array $params Paramètres à valider
     *                    - language: Code de langue (optionnel)
     *                    - force_language: Booléen (optionnel)
     * @return array Résultat de validation
     */
    public static function validateTranscriptionParams($params)
    {
        $result = [
            'valid' => true,
            'sanitized' => []
        ];
        
        // Valider le code de langue si fourni
        if (isset($params['language']) && $params['language'] !== 'auto' && $params['language'] !== '') {
            $language = trim(strtolower($params['language']));
            
            // Liste de codes de langue valides (ISO 639-1)
            $valid_languages = ['fr', 'en', 'es', 'de', 'it', 'pt', 'ru', 'ja', 'zh', 'ar', 'hi', 'ko'];
            
            if (!in_array($language, $valid_languages)) {
                return [
                    'valid' => false,
                    'error' => 'Code de langue invalide. Valeurs acceptées: ' . implode(', ', $valid_languages)
                ];
            }
            
            $result['sanitized']['language'] = $language;
        } else {
            $result['sanitized']['language'] = 'auto';
        }
        
        // Valider force_language
        if (isset($params['force_language'])) {
            $result['sanitized']['force_language'] = (bool)$params['force_language'];
        } else {
            $result['sanitized']['force_language'] = false;
        }
        
        return $result;
    }
    
    /**
     * Valide les paramètres de paraphrase
     * 
     * @param array $params Paramètres à valider
     *                    - style: Style de paraphrase
     *                    - language: Code de langue
     * @return array Résultat de validation
     */
    public static function validateParaphraseParams($params)
    {
        $result = [
            'valid' => true,
            'sanitized' => []
        ];
        
        // Valider le style
        $valid_styles = ['standard', 'simple', 'formel', 'academique', 'creatif', 'professionnel', 'concis'];
        
        if (isset($params['style'])) {
            $style = trim(strtolower($params['style']));
            
            if (!in_array($style, $valid_styles)) {
                return [
                    'valid' => false,
                    'error' => 'Style de paraphrase invalide. Valeurs acceptées: ' . implode(', ', $valid_styles)
                ];
            }
            
            $result['sanitized']['style'] = $style;
        } else {
            $result['sanitized']['style'] = 'standard';
        }
        
        // Valider le code de langue
        if (isset($params['language'])) {
            $language = trim(strtolower($params['language']));
            $valid_languages = ['fr', 'en', 'es', 'de', 'it', 'pt'];
            
            if (!in_array($language, $valid_languages)) {
                return [
                    'valid' => false,
                    'error' => 'Code de langue invalide pour la paraphrase. Valeurs acceptées: ' . implode(', ', $valid_languages)
                ];
            }
            
            $result['sanitized']['language'] = $language;
        } else {
            $result['sanitized']['language'] = 'fr';
        }
        
        return $result;
    }
    
    /**
     * Vérifie si un fichier est sécurisé (pas d'exécutable déguisé)
     * 
     * @param string $filePath Chemin du fichier
     * @return bool True si le fichier est sécurisé
     */
    private static function isSecureFile($filePath)
    {
        // Tableau des signatures de fichiers exécutables
        $executable_signatures = [
            "\x4D\x5A", // Signature EXE (MZ)
            "<?php",    // PHP
            "#!/",      // Shebang (scripts shell)
            "\x7F\x45\x4C\x46", // ELF (exécutables Linux)
            "\xCA\xFE\xBA\xBE", // Mach-O (exécutables macOS)
            "\xCF\xFA\xED\xFE", // Mach-O 64-bit
        ];
        
        // Lire les premiers octets du fichier pour vérifier les signatures
        $handle = fopen($filePath, "rb");
        $chunk = fread($handle, 1024); // Lire les premiers 1024 octets
        fclose($handle);
        
        foreach ($executable_signatures as $signature) {
            if (strpos($chunk, $signature) === 0) {
                return false; // Signature d'exécutable trouvée
            }
        }
        
        // Sécurité supplémentaire pour les fichiers PHP (pas seulement au début)
        if (stripos($chunk, '<?php') !== false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Génère un nom de fichier sécurisé et aléatoire
     * 
     * @param string $originalName Nom original du fichier
     * @param string $mimeType Type MIME du fichier
     * @return string Nom de fichier sécurisé
     */
    public static function generateSecureFilename($originalName, $mimeType)
    {
        // Déterminer l'extension basée sur le type MIME
        $extension = '.bin'; // Par défaut
        
        // Combiner les types audio et vidéo
        $mime_extensions = array_merge(self::SUPPORTED_AUDIO_TYPES, self::SUPPORTED_VIDEO_TYPES);
        
        if (isset($mime_extensions[$mimeType])) {
            $extension = $mime_extensions[$mimeType];
        } else {
            // Fallback: essayer d'obtenir l'extension depuis le nom original
            $pathInfo = pathinfo($originalName);
            if (isset($pathInfo['extension'])) {
                $extension = '.' . strtolower($pathInfo['extension']);
            }
        }
        
        // Générer un identifiant unique
        $uniqueId = uniqid('file_', true);
        
        // Générer un hash basé sur l'heure et un nombre aléatoire
        $hash = substr(md5(time() . mt_rand()), 0, 8);
        
        // Construire le nom de fichier sécurisé
        return $uniqueId . '_' . $hash . $extension;
    }
}