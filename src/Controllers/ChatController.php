<?php

namespace Controllers;

use Services\ChatService;
use Services\TranscriptionService;
use Utils\ResponseUtils;

/**
 * Contrôleur pour le chat contextuel
 */
class ChatController
{
    /**
     * @var ChatService
     */
    private $chatService;

    /**
     * @var TranscriptionService
     */
    private $transcriptionService;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->chatService = new ChatService();
        $this->transcriptionService = new TranscriptionService();
    }

    /**
     * Traite une requête de chat
     * 
     * @return array Résultat du chat
     */
    public function handleChatRequest()
    {
        // Vérifier si un message a été fourni
        if (!isset($_POST['message']) || empty($_POST['message'])) {
            return [
                'success' => false,
                'error' => 'Aucun message fourni'
            ];
        }

        // Récupérer les paramètres
        $message = $_POST['message'];
        $context = isset($_POST['context']) ? json_decode($_POST['context'], true) : [];
        $transcriptionId = $_POST['transcription_id'] ?? null;

        // Envoyer le message
        $result = $this->chatService->sendMessage($message, $context, $transcriptionId);

        // Retourner le résultat
        return $result;
    }

    /**
     * Exporte une conversation
     * 
     * @return array Résultat de l'export
     */
    public function exportConversation()
    {
        // Vérifier si des messages ont été fournis
        if (!isset($_POST['messages']) || empty($_POST['messages'])) {
            return [
                'success' => false,
                'error' => 'Aucun message à exporter'
            ];
        }

        // Récupérer les messages
        $messages = json_decode($_POST['messages'], true);

        // Exporter la conversation
        $result = $this->chatService->exportConversation($messages);

        // Retourner le résultat
        return $result;
    }

    /**
     * Affiche la page de chat
     * 
     * @return array Données pour l'affichage de la page
     */
    public function showChatPage()
    {
        $transcriptionId = $_GET['transcription_id'] ?? null;
        $transcriptionText = '';

        // Récupérer le texte de la transcription si un ID est fourni
        if ($transcriptionId) {
            $result = $this->transcriptionService->getTranscriptionResult($transcriptionId);
            if ($result['success']) {
                $transcriptionText = $result['text'];
            }
        }

        // Préparer les données pour l'affichage
        return [
            'transcription_id' => $transcriptionId,
            'transcription_text' => $transcriptionText
        ];
    }

    /**
     * Télécharge un fichier d'export de conversation
     * 
     * @param string $filename Nom du fichier à télécharger
     */
    public function downloadExport($filename)
    {
        $filePath = EXPORT_DIR . '/' . $filename;

        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            ResponseUtils::redirectWithError('result_not_found', 'Fichier d\'export non trouvé');
        }

        // Définir les en-têtes pour le téléchargement
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));

        // Envoyer le contenu
        readfile($filePath);
        exit;
    }
}
