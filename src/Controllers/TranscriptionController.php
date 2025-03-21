<?php

namespace Controllers;

use Services\TranscriptionService;
use Services\YouTubeService;
use Utils\ResponseUtils;

/**
 * Contrôleur pour la transcription audio
 */
class TranscriptionController
{
    /**
     * @var TranscriptionService
     */
    private $transcriptionService;

    /**
     * @var YouTubeService
     */
    private $youtubeService;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->transcriptionService = new TranscriptionService();
        $this->youtubeService = new YouTubeService();
    }

    /**
     * Traite une requête de transcription de fichier audio
     */
    public function handleFileUpload()
    {
        // Enregistrer les informations de débogage
        $debug_info = [
            'FILES' => $_FILES,
            'POST' => $_POST,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
        ];
        file_put_contents(BASE_DIR . '/debug_upload.log', print_r($debug_info, true));

        // Vérifier si un fichier a été téléchargé
        if (!isset($_FILES['audio_file'])) {
            ResponseUtils::redirectWithError('upload', 'Aucun fichier téléchargé');
        }

        // Récupérer les paramètres
        $file = $_FILES['audio_file'];
        $language = $_POST['language'] ?? 'auto';
        $forceLanguage = isset($_POST['force_language']) ? true : false;

        // Traiter le fichier
        $result = $this->transcriptionService->processUploadedFile($file, $language, $forceLanguage);

        // Vérifier si la transcription a réussi
        if (!$result['success']) {
            ResponseUtils::redirectWithError('transcription', $result['error']);
        }

        // Rediriger vers la page de résultat
        ResponseUtils::redirect('result.php?id=' . $result['result_id']);
    }

    /**
     * Traite une requête de transcription de vidéo YouTube
     */
    public function handleYouTubeDownload()
    {
        // Vérifier si une URL YouTube a été fournie
        if (!isset($_POST['youtube_url']) || empty($_POST['youtube_url'])) {
            ResponseUtils::redirectWithError('youtube', 'Aucune URL YouTube fournie');
        }

        // Récupérer les paramètres
        $youtubeUrl = $_POST['youtube_url'];
        $language = $_POST['language'] ?? 'auto';
        $forceLanguage = isset($_POST['force_language']) ? true : false;

        // Télécharger et transcrire la vidéo
        $result = $this->youtubeService->downloadAndTranscribe($youtubeUrl, $language, $forceLanguage);

        // Vérifier si la transcription a réussi
        if (!$result['success']) {
            ResponseUtils::redirectWithError('youtube', $result['error']);
        }

        // Rediriger vers la page de résultat
        ResponseUtils::redirect('result.php?id=' . $result['result_id']);
    }

    /**
     * Affiche le résultat d'une transcription
     */
    public function showResult()
    {
        // Vérifier si un ID de résultat a été fourni
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            ResponseUtils::redirectWithError('missing_id');
        }

        $resultId = $_GET['id'];

        // Récupérer le résultat
        $result = $this->transcriptionService->getTranscriptionResult($resultId);

        // Vérifier si le résultat a été trouvé
        if (!$result['success']) {
            ResponseUtils::redirectWithError('result_not_found');
        }

        // Retourner le résultat pour l'affichage
        return $result;
    }

    /**
     * Télécharge un résultat de transcription
     */
    public function downloadResult()
    {
        // Vérifier si un ID de résultat a été fourni
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            ResponseUtils::redirectWithError('missing_id');
        }

        $resultId = $_GET['id'];

        // Récupérer le résultat
        $result = $this->transcriptionService->getTranscriptionResult($resultId);

        // Vérifier si le résultat a été trouvé
        if (!$result['success']) {
            ResponseUtils::redirectWithError('result_not_found');
        }

        // Définir les en-têtes pour le téléchargement
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="transcription_' . $resultId . '.txt"');
        header('Content-Length: ' . strlen($result['text']));

        // Envoyer le contenu
        echo $result['text'];
        exit;
    }
}
