<?php

/**
 * Gestionnaire de contexte pour stocker les résultats de transcription
 */

class ContextManager
{
    private static $instance = null;
    private $context = [
        'transcription' => '',
        'translation' => '',
        'metadata' => []
    ];
    private $sessionId = null;

    private function __construct()
    {
        // Initialiser la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->sessionId = session_id();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function updateContext($transcription = null, $translation = null, $metadata = null)
    {
        if ($transcription !== null) {
            $this->context['transcription'] = $transcription;
            $_SESSION['transcription'] = $transcription;
        }

        if ($translation !== null) {
            $this->context['translation'] = $translation;
            $_SESSION['translation'] = $translation;
        }

        if ($metadata !== null) {
            $this->context['metadata'] = array_merge($this->context['metadata'], $metadata);
            $_SESSION['metadata'] = $this->context['metadata'];
        }
    }

    public function getContext()
    {
        // Récupérer depuis la session si disponible
        if (isset($_SESSION['transcription'])) {
            $this->context['transcription'] = $_SESSION['transcription'];
        }

        if (isset($_SESSION['translation'])) {
            $this->context['translation'] = $_SESSION['translation'];
        }

        if (isset($_SESSION['metadata'])) {
            $this->context['metadata'] = $_SESSION['metadata'];
        }

        return $this->context;
    }

    public function clearContext()
    {
        $this->context = [
            'transcription' => '',
            'translation' => '',
            'metadata' => []
        ];

        // Nettoyer la session
        unset($_SESSION['transcription']);
        unset($_SESSION['translation']);
        unset($_SESSION['metadata']);
    }
}
