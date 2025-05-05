<?php

namespace Middleware;

use Utils\ValidationUtils;

/**
 * Middleware pour la validation des entrées
 */
class ValidationMiddleware
{
    /**
     * Valide les données reçues du client
     * 
     * @param array $requestData Données à valider
     * @param array $rules Règles de validation
     * @return array Résultat de la validation avec 'success', 'errors' et 'sanitized'
     */
    public static function validate($requestData, $rules)
    {
        $errors = [];
        $sanitized = [];
        
        foreach ($rules as $field => $rule) {
            // Vérifier si le champ est requis et présent
            if (isset($rule['required']) && $rule['required'] && !isset($requestData[$field])) {
                $errors[$field] = 'Le champ ' . $field . ' est requis';
                continue;
            }
            
            // Si le champ n'est pas présent et n'est pas requis, passer au suivant
            if (!isset($requestData[$field])) {
                continue;
            }
            
            $value = $requestData[$field];
            
            // Valider selon le type
            switch ($rule['type']) {
                case 'string':
                    $result = self::validateString($value, $rule);
                    break;
                case 'number':
                    $result = self::validateNumber($value, $rule);
                    break;
                case 'email':
                    $result = self::validateEmail($value);
                    break;
                case 'boolean':
                    $result = self::validateBoolean($value);
                    break;
                case 'youtube_url':
                    $result = ValidationUtils::validateYoutubeUrl($value);
                    break;
                case 'file':
                    $result = ValidationUtils::validateUploadedFile($value, $rule['options'] ?? []);
                    break;
                case 'language':
                    $result = self::validateLanguage($value);
                    break;
                case 'enum':
                    $result = self::validateEnum($value, $rule['options'] ?? []);
                    break;
                default:
                    $result = ['valid' => false, 'error' => 'Type de validation non supporté'];
            }
            
            if (!$result['valid']) {
                $errors[$field] = $result['error'];
            } else {
                // Stocker la valeur sanitizée
                $sanitized[$field] = isset($result['sanitized']) ? $result['sanitized'] : 
                                    (isset($result['normalized_url']) ? $result['normalized_url'] : $value);
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'sanitized' => $sanitized
        ];
    }
    
    /**
     * Valide une chaîne de caractères
     * 
     * @param string $value Valeur à valider
     * @param array $rule Règles de validation
     * @return array Résultat de la validation
     */
    private static function validateString($value, $rule)
    {
        if (!is_string($value)) {
            return ['valid' => false, 'error' => 'La valeur doit être une chaîne de caractères'];
        }
        
        $min = $rule['min'] ?? 0;
        $max = $rule['max'] ?? PHP_INT_MAX;
        $pattern = $rule['pattern'] ?? null;
        $strip_tags = $rule['strip_tags'] ?? true;
        
        // Sanitizer la valeur
        $value = trim($value);
        
        if ($strip_tags) {
            $value = strip_tags($value);
        }
        
        // Vérifier la longueur
        $length = mb_strlen($value);
        if ($length < $min) {
            return ['valid' => false, 'error' => 'La valeur doit contenir au moins ' . $min . ' caractères'];
        }
        
        if ($length > $max) {
            return ['valid' => false, 'error' => 'La valeur ne doit pas dépasser ' . $max . ' caractères'];
        }
        
        // Vérifier le pattern
        if ($pattern && !preg_match($pattern, $value)) {
            return ['valid' => false, 'error' => 'La valeur ne correspond pas au format attendu'];
        }
        
        return ['valid' => true, 'sanitized' => $value];
    }
    
    /**
     * Valide un nombre
     * 
     * @param mixed $value Valeur à valider
     * @param array $rule Règles de validation
     * @return array Résultat de la validation
     */
    private static function validateNumber($value, $rule)
    {
        if (!is_numeric($value)) {
            return ['valid' => false, 'error' => 'La valeur doit être un nombre'];
        }
        
        $min = $rule['min'] ?? PHP_INT_MIN;
        $max = $rule['max'] ?? PHP_INT_MAX;
        $integer = $rule['integer'] ?? false;
        
        // Convertir en nombre
        $value = $integer ? (int)$value : (float)$value;
        
        // Vérifier les limites
        if ($value < $min) {
            return ['valid' => false, 'error' => 'La valeur doit être supérieure ou égale à ' . $min];
        }
        
        if ($value > $max) {
            return ['valid' => false, 'error' => 'La valeur doit être inférieure ou égale à ' . $max];
        }
        
        return ['valid' => true, 'sanitized' => $value];
    }
    
    /**
     * Valide un email
     * 
     * @param string $value Valeur à valider
     * @return array Résultat de la validation
     */
    private static function validateEmail($value)
    {
        if (!is_string($value)) {
            return ['valid' => false, 'error' => 'L\'email doit être une chaîne de caractères'];
        }
        
        $value = trim($value);
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'L\'email n\'est pas valide'];
        }
        
        return ['valid' => true, 'sanitized' => $value];
    }
    
    /**
     * Valide un booléen
     * 
     * @param mixed $value Valeur à valider
     * @return array Résultat de la validation
     */
    private static function validateBoolean($value)
    {
        if (is_bool($value)) {
            return ['valid' => true, 'sanitized' => $value];
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            
            if (in_array($value, ['true', '1', 'yes', 'oui'])) {
                return ['valid' => true, 'sanitized' => true];
            }
            
            if (in_array($value, ['false', '0', 'no', 'non'])) {
                return ['valid' => true, 'sanitized' => false];
            }
        }
        
        if (is_numeric($value)) {
            return ['valid' => true, 'sanitized' => (bool)$value];
        }
        
        return ['valid' => false, 'error' => 'La valeur doit être un booléen'];
    }
    
    /**
     * Valide un code de langue
     * 
     * @param string $value Valeur à valider
     * @return array Résultat de la validation
     */
    private static function validateLanguage($value)
    {
        if (!is_string($value)) {
            return ['valid' => false, 'error' => 'Le code de langue doit être une chaîne de caractères'];
        }
        
        $value = trim(strtolower($value));
        
        // Autoriser 'auto' ou une chaîne vide pour la détection automatique
        if ($value === 'auto' || $value === '') {
            return ['valid' => true, 'sanitized' => 'auto'];
        }
        
        // Liste de codes de langue valides (ISO 639-1)
        $valid_languages = ['fr', 'en', 'es', 'de', 'it', 'pt', 'ru', 'ja', 'zh', 'ar', 'hi', 'ko'];
        
        if (!in_array($value, $valid_languages)) {
            return [
                'valid' => false, 
                'error' => 'Code de langue invalide. Valeurs acceptées: auto, ' . implode(', ', $valid_languages)
            ];
        }
        
        return ['valid' => true, 'sanitized' => $value];
    }
    
    /**
     * Valide une valeur parmi une liste d'options
     * 
     * @param string $value Valeur à valider
     * @param array $options Options de validation avec 'values' contenant les valeurs autorisées
     * @return array Résultat de la validation
     */
    private static function validateEnum($value, $options)
    {
        if (!isset($options['values']) || !is_array($options['values'])) {
            return ['valid' => false, 'error' => 'Liste des valeurs autorisées non définie'];
        }
        
        $values = $options['values'];
        $case_sensitive = $options['case_sensitive'] ?? false;
        
        if (is_string($value) && !$case_sensitive) {
            $value = strtolower(trim($value));
            $values = array_map('strtolower', $values);
        }
        
        if (!in_array($value, $values, true)) {
            return [
                'valid' => false, 
                'error' => 'Valeur non autorisée. Valeurs acceptées: ' . implode(', ', $options['values'])
            ];
        }
        
        return ['valid' => true, 'sanitized' => $value];
    }
    
    /**
     * Applique les règles de validation pour une action spécifique
     * 
     * @param string $controller Nom du contrôleur
     * @param string $action Nom de l'action
     * @param array $data Données à valider
     * @return array Résultat de la validation
     */
    public static function validateRoute($controller, $action, $data)
    {
        $rules = self::getValidationRules($controller, $action);
        
        if (!$rules) {
            // Pas de règles définies pour cette route, tout est valide
            return ['success' => true, 'sanitized' => $data];
        }
        
        return self::validate($data, $rules);
    }
    
    /**
     * Récupère les règles de validation pour une action spécifique
     * 
     * @param string $controller Nom du contrôleur
     * @param string $action Nom de l'action
     * @return array|null Règles de validation ou null si non définies
     */
    private static function getValidationRules($controller, $action)
    {
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
                        'type' => 'language',
                        'required' => false
                    ],
                    'force_language' => [
                        'type' => 'boolean',
                        'required' => false
                    ]
                ],
                'getResult' => [
                    'result_id' => [
                        'type' => 'string',
                        'required' => true,
                        'pattern' => '/^[a-zA-Z0-9_]+$/'
                    ]
                ],
                'deleteResult' => [
                    'result_id' => [
                        'type' => 'string',
                        'required' => true,
                        'pattern' => '/^[a-zA-Z0-9_]+$/'
                    ]
                ]
            ],
            'YouTubeController' => [
                'downloadAndTranscribe' => [
                    'youtube_url' => [
                        'type' => 'youtube_url',
                        'required' => true
                    ],
                    'language' => [
                        'type' => 'language',
                        'required' => false
                    ],
                    'force_language' => [
                        'type' => 'boolean',
                        'required' => false
                    ]
                ]
            ],
            'ParaphraseController' => [
                'paraphraseText' => [
                    'text' => [
                        'type' => 'string',
                        'required' => true,
                        'min' => 10,
                        'max' => 20000
                    ],
                    'style' => [
                        'type' => 'enum',
                        'required' => false,
                        'options' => [
                            'values' => ['standard', 'simple', 'formel', 'academique', 'creatif', 'professionnel', 'concis']
                        ]
                    ],
                    'language' => [
                        'type' => 'language',
                        'required' => false
                    ]
                ],
                'paraphraseTranscription' => [
                    'result_id' => [
                        'type' => 'string',
                        'required' => true,
                        'pattern' => '/^[a-zA-Z0-9_]+$/'
                    ],
                    'style' => [
                        'type' => 'enum',
                        'required' => false,
                        'options' => [
                            'values' => ['standard', 'simple', 'formel', 'academique', 'creatif', 'professionnel', 'concis']
                        ]
                    ],
                    'language' => [
                        'type' => 'language',
                        'required' => false
                    ]
                ]
            ],
            'ChatController' => [
                'sendMessage' => [
                    'message' => [
                        'type' => 'string',
                        'required' => true,
                        'min' => 1,
                        'max' => 4000
                    ],
                    'transcription_id' => [
                        'type' => 'string',
                        'required' => false,
                        'pattern' => '/^[a-zA-Z0-9_]+$/'
                    ]
                ],
                'getConversation' => [
                    'conversation_id' => [
                        'type' => 'string',
                        'required' => true,
                        'pattern' => '/^[a-zA-Z0-9_]+$/'
                    ]
                ],
                'deleteConversation' => [
                    'conversation_id' => [
                        'type' => 'string',
                        'required' => true,
                        'pattern' => '/^[a-zA-Z0-9_]+$/'
                    ]
                ],
                'exportConversation' => [
                    'conversation_id' => [
                        'type' => 'string',
                        'required' => true,
                        'pattern' => '/^[a-zA-Z0-9_]+$/'
                    ]
                ]
            ]
        ];
        
        return isset($validationRules[$controller][$action]) ? $validationRules[$controller][$action] : null;
    }
}