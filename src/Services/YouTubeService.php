<?php

namespace Services;

use Utils\FileUtils;
use Utils\YouTubeUtils;

/**
 * Service pour le téléchargement et la transcription de vidéos YouTube
 */
class YouTubeService
{
    /**
     * @var TranscriptionService
     */
    private $transcriptionService;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->transcriptionService = new TranscriptionService();
    }

    /**
     * Télécharge et transcrit une vidéo YouTube
     * 
     * @param string $youtubeUrl URL YouTube
     * @param string $language Code de langue
     * @param bool $forceLanguage Forcer la traduction dans la langue spécifiée
     * @return array Résultat du traitement
     */
    public function downloadAndTranscribe($youtubeUrl, $language = 'auto', $forceLanguage = false)
    {
        // Valider les paramètres de transcription
        $paramsValidation = \Utils\ValidationUtils::validateTranscriptionParams([
            'language' => $language,
            'force_language' => $forceLanguage
        ]);
        
        if (!$paramsValidation['valid']) {
            return [
                'success' => false,
                'error' => $paramsValidation['error'],
                'category' => 'validation',
                'advice' => 'Veuillez vérifier les paramètres de transcription fournis.'
            ];
        }
        
        // Extraire les paramètres validés
        $language = $paramsValidation['sanitized']['language'];
        $forceLanguage = $paramsValidation['sanitized']['force_language'];
        
        // Valider l'URL YouTube
        $urlValidation = \Utils\ValidationUtils::validateYoutubeUrl($youtubeUrl);
        
        if (!$urlValidation['valid']) {
            return [
                'success' => false,
                'error' => $urlValidation['error'],
                'category' => 'validation',
                'advice' => 'Veuillez fournir une URL YouTube valide, par exemple: https://www.youtube.com/watch?v=VIDEO_ID'
            ];
        }
        
        // Utiliser l'URL normalisée pour uniformiser les formats
        $youtubeUrl = $urlValidation['normalized_url'];
        $youtubeId = $urlValidation['video_id'];

        // Créer le répertoire de téléchargement si nécessaire
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }

        // Générer un nom de fichier unique
        $uniqueId = uniqid('audio_');
        $filename = $uniqueId . '_' . YouTubeUtils::getYoutubeVideoId($youtubeUrl) . '.mp3';
        $filePath = UPLOAD_DIR . '/' . $filename;

        // Télécharger la vidéo YouTube
        $downloadResult = YouTubeUtils::downloadYoutubeVideo($youtubeUrl, $filePath, VIDEO_DOWNLOAD_API_KEY);
        if (!$downloadResult['success']) {
            return [
                'success' => false,
                'error' => $downloadResult['error']
            ];
        }

        // Créer le répertoire temporaire pour les fichiers prétraités
        $tempDir = TEMP_AUDIO_DIR;
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Prétraiter le fichier audio
        $preprocessResult = $this->transcriptionService->preprocessAudio($filePath, $tempDir);
        if (!$preprocessResult['success']) {
            return [
                'success' => false,
                'error' => $preprocessResult['error']
            ];
        }

        // Utiliser le fichier prétraité pour la transcription
        $preprocessedFilePath = $preprocessResult['output_file'];

        // Créer le répertoire de résultats si nécessaire
        if (!is_dir(RESULT_DIR)) {
            mkdir(RESULT_DIR, 0777, true);
        }

        // Générer un nom de fichier pour le résultat
        $resultId = FileUtils::generateUniqueId();
        $resultPath = RESULT_DIR . '/' . $resultId . '.json';

        // Transcrire le fichier audio
        $transcriptionResult = $this->transcriptionService->transcribeAudio($preprocessedFilePath, $resultPath, $language, $forceLanguage);
        if (!$transcriptionResult['success']) {
            return [
                'success' => false,
                'error' => $transcriptionResult['error']
            ];
        }

        return [
            'success' => true,
            'result_id' => $resultId,
            'text' => $transcriptionResult['text'],
            'language' => $transcriptionResult['language'],
            'youtube_url' => $youtubeUrl,
            'youtube_id' => YouTubeUtils::getYoutubeVideoId($youtubeUrl)
        ];
    }
}
